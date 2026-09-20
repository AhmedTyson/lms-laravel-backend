#!/usr/bin/env python3
"""jev_audit.py - yes/no (Noul) decision audit of the LMS repo with TypeSafe Jev.

Design (per TypeSafe guidance): CODE owns exact facts and policy (commit-message format,
composer.json changed?, ADR file added?, labeled-secret regex, phase parsing). JEV owns the
semantic judgments (is this a real-looking credential? is this behavior misplaced? does this
migration enforce that rule?). Independent questions over one state go in ONE request.

Default is a dry run: no network, no key needed. Pass --live (and set TYPESAFE_API_KEY in
your environment, never in a committed file) to call Jev. Stdlib only, no new dependency.

  python jev_audit.py --repo /path/to/lms-laravel-backend --mode both --limit 8
  TYPESAFE_API_KEY=... python jev_audit.py --repo ... --live --validate
"""
import argparse, fnmatch, glob, json, os, re, subprocess, sys, time
import urllib.error, urllib.request
from pathlib import Path

HERE = Path(__file__).resolve().parent
CATALOG = json.loads((HERE / "questions.json").read_text(encoding="utf-8"))
TH = CATALOG["thresholds"]
API_URL = os.environ.get("TYPESAFE_BASE_URL", "https://api.typesafe.ai/v1/systemone")
MODEL = os.environ.get("TYPESAFE_MODEL", "jev-latest")

# Jev limits: 64k tokens total, 32k for state + longest question. Stay well under.
MAX_STATE_TOKENS = 20_000
DIFF_CHAR_CAP = 14_000

SKIP_DIFF = ("composer.lock", "package-lock.json", "yarn.lock", "pnpm-lock.yaml")
KERNEL_PREFIXES = ("app/Models/User.php", "app/Providers/", "app/Console/")
CONVENTIONAL = re.compile(r"^(feat|fix|chore|docs|test|refactor)(\([^)]+\))?!?: .+")

# Code-owned secret detection: only tokens with a known credential shape or assignment.
LABELED_SECRET = [
    re.compile(r"\b(?:apikey|api_key|sk|pk|ghp|gho|ghs|glpat)[_-][A-Za-z0-9_\-]{20,}"),
    re.compile(r"\bxox[baprs]-[A-Za-z0-9-]{10,}"),
    re.compile(r"\bAKIA[0-9A-Z]{16}\b"),
    re.compile(r"(?im)^[A-Z0-9_]*(?:KEY|SECRET|TOKEN|PASSWORD)[A-Z0-9_]*=\S{16,}$"),
]
TOKEN = re.compile(r"[A-Za-z0-9_\-+/]{32,}")

POLICY = {
    "secrets": "Secrets live in env only. git log -p must never show a real key, token or password. (handbook 05 s1)",
    "layout": "Root app/ is kernel only (User, providers, console). Behavior lives in the owning module. Shared code in app/ needs justification. (AGENTS.md, handbook 06 s3)",
    "adr": "Any new package, pattern or cross-module contract needs docs/adr/NNNN-name.md before the code lands. (handbook 06 s1, DoD 07)",
    "tests": "Pest, in-memory SQLite, no live services. Every cited RULE needs a test naming it. Flaky tests are defects. (handbook 03)",
    "phases": "Phases are worked strictly in order; a business api route before its phase fails ModulesSmokeTest. (handbook 01 s5, todo.md)",
    "scope": "One task = one PR; no unrelated changes in a commit. (handbook 01 s3, 04 s4)",
}


# ----------------------------------------------------------------------------- helpers
def git(repo, *args):
    return subprocess.run(["git", "-C", str(repo), *args], capture_output=True,
                          text=True, check=True).stdout


def est_tokens(obj):
    return len(json.dumps(obj, ensure_ascii=False)) // 4


def _secretish(tok):
    if tok.count("/") >= 2:
        return False
    return bool(re.search(r"\d", tok) and re.search(r"[A-Za-z]", tok))


def redact(text):
    """Replace long token-like strings with a shape marker. Secrets never leave the machine."""
    def sub(m):
        t = m.group(0)
        if not _secretish(t):
            return t
        p = re.match(r"[A-Za-z]{2,10}(?=[_-])", t)
        return f'<SECRET-LIKE len={len(t)} prefix="{p.group(0) + "_" if p else ""}">'
    return TOKEN.sub(sub, text)


def labeled_secret_hits(text):
    return sum(len(p.findall(text)) for p in LABELED_SECRET)


def question_payload(items):
    out = {}
    for q in items:
        out[q["id"]] = {"type": "noul", "instructions": q["instructions"], "criteria": q["criteria"]}
    return out


def call_jev(state, questions, key):
    body = json.dumps({"state": state, "model": MODEL, "questions": questions}).encode()
    req = urllib.request.Request(API_URL, data=body, headers={
        "Authorization": f"Bearer {key}", "Content-Type": "application/json",
        "Accept": "application/json"})
    for attempt in range(3):
        try:
            with urllib.request.urlopen(req, timeout=45) as r:
                return json.loads(r.read())
        except urllib.error.HTTPError as e:
            if e.code in (429, 500, 502, 503, 504) and attempt < 2:
                time.sleep(2 ** attempt)
                continue
            raise RuntimeError(f"Jev HTTP {e.code}") from None  # never echo body/headers
        except urllib.error.URLError as e:
            if attempt < 2:
                time.sleep(2 ** attempt)
                continue
            raise RuntimeError(f"Jev network error: {e.reason}") from None


def p_of(resp, qid):
    return float(resp["answers"][qid]["noul"])


def band(p):
    return "high" if p >= TH["high"] else "low" if p <= TH["low"] else "uncertain"



def new_packages(repo, sha):
    """Packages newly added to composer require/require-dev in this commit (exact, no LLM)."""
    def pkgs(rev):
        try:
            data = json.loads(git(repo, "show", f"{rev}:composer.json"))
        except (subprocess.CalledProcessError, json.JSONDecodeError):
            return set()
        names = set(data.get("require", {})) | set(data.get("require-dev", {}))
        return {n for n in names if n != "php" and not n.startswith("ext-")}
    return sorted(pkgs(sha) - pkgs(sha + "^"))

# ----------------------------------------------------------------------------- repo context
def repo_context(repo):
    todo = (Path(repo) / "tasks" / "todo.md").read_text(encoding="utf-8", errors="ignore")
    rows = re.findall(r"^\| (Phase \d+) — ([^|]+?)\s*\| (\w+) \|", todo, re.M)
    phases = [{"phase": a, "title": re.sub(r"\s*\[x\]\s*$", "", b), "status": c} for a, b, c in rows]
    current = next((p for p in phases if p["status"] != "done"), None)
    return {"current_phase": f'{current["phase"]} — {current["title"]}' if current else "unknown",
            "phases": [f'{p["phase"]}: {p["title"]} ({p["status"]})' for p in phases]}


# ----------------------------------------------------------------------------- commits mode
def commit_material(repo, sha):
    subject = git(repo, "show", "-s", "--format=%s", sha).strip()
    files = []
    for line in git(repo, "show", "--name-status", "--format=", sha).splitlines():
        parts = line.split("\t")
        files.append({"status": parts[0][0], "path": parts[-1]})
    diff = git(repo, "show", "--unified=0", "--no-color", "--format=", sha, "--", ".",
               *[f":(exclude){x}" for x in SKIP_DIFF])
    added, cur = {}, None
    for line in diff.splitlines():
        if line.startswith("diff --git"):
            cur = line.split(" b/", 1)[-1]
            added.setdefault(cur, [])
        elif cur and line.startswith("+") and not line.startswith("+++"):
            added[cur].append(line[1:])
    return subject, files, added


def build_commit(repo, sha, ctx):
    subject, files, added = commit_material(repo, sha)
    paths = [f["path"] for f in files]
    raw_all = "\n".join("\n".join(v) for v in added.values())

    # ---- code-owned facts (exact, deterministic)
    facts = {
        "subject_ok": bool(CONVENTIONAL.match(subject)),
        "subject_len": len(subject),
        "new_packages": new_packages(repo, sha),
        "adr_added": any(f["status"] == "A" and f["path"].startswith("docs/adr/") for f in files),
        "secret_hits": labeled_secret_hits(raw_all),
        "root_app_paths": [p for p in paths if p.startswith("app/") and not p.startswith(KERNEL_PREFIXES)],
        "tests_changed": [p for p in paths if p.startswith("tests/") or "/tests/" in p],
    }
    test_text = "\n".join("\n".join(added.get(p, [])) for p in facts["tests_changed"])
    rule_re = re.compile(r"\b(?:RULE|SCOPE)-\d+")
    facts["rules_in_change"] = sorted(set(rule_re.findall(subject + "\n" + raw_all)))
    facts["rules_in_tests"] = sorted(set(rule_re.findall(test_text)))

    # ---- state for Jev: capped, redacted excerpt
    budget, excerpt = DIFF_CHAR_CAP, {}
    for path, lines in added.items():
        cap = 15 if path.endswith(".md") else 80
        text = redact("\n".join(lines[:cap]))[:min(budget, 3000)]
        if text:
            excerpt[path] = text
            budget -= len(text)
        if budget <= 0:
            break
    state = {
        "commit": {"subject": subject,
                   "changed_files": [f'{f["status"]} {f["path"]}' for f in files][:60],
                   "added_lines_excerpt": excerpt},
        "repo_context": ctx,
        "policy": POLICY,
    }
    return subject, facts, state


def judge_commit(facts, resp):
    """Compose code facts + Jev probabilities into findings. Policy stays explicit here."""
    F = []
    add = lambda sev, code, msg: F.append({"severity": sev, "code": code, "message": msg})

    # code-only checks
    if not facts["subject_ok"]:
        add("FLAG", "commit-format", "Subject is not Conventional-Commits (handbook 01 s2)")
    elif facts["subject_len"] > 72:
        add("NOTE", "subject-too-long", f'Subject is {facts["subject_len"]} chars, limit 72 (handbook 01 s2)')
    if facts["new_packages"] and not facts["adr_added"]:
        add("FLAG", "package-without-adr", f'new package(s) {", ".join(facts["new_packages"])} but no new docs/adr file (handbook 06 s1)')
    if facts["secret_hits"]:
        add("BLOCK", "secret-regex", f'{facts["secret_hits"]} labeled credential pattern(s) in added lines (handbook 05 s1)')

    if resp is None:
        return F
    p = lambda q: p_of(resp, q)

    # jev + code composition
    if p("secret_committed") >= TH["high"] and not facts["secret_hits"]:
        add("BLOCK", "secret-jev", f'Jev p(real-looking credential)={p("secret_committed"):.2f}, regex found none: verify by hand')
    if p("new_dependency_or_pattern") >= TH["high"] and not facts["adr_added"]:
        add("FLAG", "pattern-without-adr", f'Jev p(new dependency/pattern)={p("new_dependency_or_pattern"):.2f} and no ADR added')
    if p("behavior_in_root_app") >= TH["high"] and facts["root_app_paths"]:
        add("FLAG", "behavior-in-root-app", f'p={p("behavior_in_root_app"):.2f}; paths: {", ".join(facts["root_app_paths"][:4])}')
    if p("test_depends_on_live_service") >= TH["high"]:
        add("FLAG", "live-service-test", f'p={p("test_depends_on_live_service"):.2f} (CI has no key/network guarantee)')
    if p("governed_rule_touched") >= TH["high"] and not facts["rules_in_tests"]:
        add("FLAG", "rule-without-named-test", f'p={p("governed_rule_touched"):.2f}; no test names a RULE/SCOPE id (handbook 03 s2)')
    if p("beyond_current_phase") >= TH["high"]:
        add("FLAG", "beyond-current-phase", f'p={p("beyond_current_phase"):.2f}')
    if p("mixed_concerns") >= TH["high"]:
        add("NOTE", "mixed-concerns", f'p={p("mixed_concerns"):.2f}')
    for q in CATALOG["commit"]:
        if q["yes_means"] != "info" and band(p(q["id"])) == "uncertain":
            add("REVIEW", f'uncertain-{q["id"]}', f'p={p(q["id"]):.2f}: near 0.5 means yes/no equally likely, send to a human')
    return F


# ----------------------------------------------------------------------------- rules mode
# Ground truth (from reading the migrations) lets --validate measure Jev on THIS domain.
RULES = [
    {"id": "ENROLL-ONCE", "match": "A student may enroll in a course only once",
     "evidence": ["Modules/Enrollment/database/migrations/*_create_enrollments_table.php"],
     "expected": {"rule_requires_db": True, "db_level_enforced": True, "contradicts_rule": False}},
    {"id": "RULE-014", "match": "(RULE-014)",
     "evidence": ["Modules/Courses/database/migrations/*_create_lessons_table.php"],
     "expected": {"rule_requires_db": True, "db_level_enforced": True, "contradicts_rule": False}},
    {"id": "RULE-015", "match": "(RULE-015, RULE-016)",
     "evidence": ["Modules/Assignments/database/migrations/*_create_submissions_table.php"],
     "expected": {"rule_requires_db": None, "db_level_enforced": True, "contradicts_rule": False}},
    {"id": "RULE-011", "match": "(RULE-011)",
     "evidence": ["Modules/AccessManagement/database/migrations/*_create_groups_table.php"],
     "expected": {"rule_requires_db": False, "db_level_enforced": None, "contradicts_rule": False}},
    {"id": "DUE-DATE", "match": "`due_date` is required and must be in the future",
     "evidence": ["Modules/Assignments/database/migrations/*_create_assignments_table.php"],
     "expected": {"rule_requires_db": False, "db_level_enforced": False, "contradicts_rule": False}},
    # Hard case on purpose: group_id uses nullOnDelete() on an append-only ledger.
    {"id": "RULE-005", "match": "(RULE-005)",
     "evidence": ["Modules/AccessManagement/database/migrations/*_create_permission_grants_table.php"],
     "expected": {"rule_requires_db": None, "db_level_enforced": None, "contradicts_rule": None}},
]


def find_spec(repo):
    return next(iter(glob.glob(str(Path(repo) / "docs" / "LMS_Laravel_BRD_PRD_v*_full.md"))), None)


def build_rule(repo, rule, spec_lines):
    text = next((l.lstrip("- ").strip() for l in spec_lines if rule["match"] in l), "(rule text not found)")
    mig = {}
    for pat in rule["evidence"]:
        for f in sorted(glob.glob(str(Path(repo) / pat))):
            src = Path(f).read_text(encoding="utf-8", errors="ignore")
            m = re.search(r"Schema::create.*?\n\s*\}\);", src, re.S)
            mig[str(Path(f).relative_to(repo))] = redact((m.group(0) if m else src)[:6000])
    return {"rule": {"id": rule["id"], "text": text}, "migration": mig}


def judge_rule(resp):
    F, p = [], (lambda q: p_of(resp, q))
    if p("rule_requires_db") >= TH["high"] and p("db_level_enforced") <= TH["low"]:
        F.append({"severity": "FLAG", "code": "db-enforcement-missing",
                  "message": f'requires_db={p("rule_requires_db"):.2f} but enforced={p("db_level_enforced"):.2f}'})
    if p("contradicts_rule") >= TH["high"]:
        F.append({"severity": "FLAG", "code": "schema-contradicts-rule", "message": f'p={p("contradicts_rule"):.2f}'})
    for q in CATALOG["rule"]:
        if band(p(q["id"])) == "uncertain":
            F.append({"severity": "REVIEW", "code": f'uncertain-{q["id"]}', "message": f'p={p(q["id"]):.2f}'})
    return F


# ----------------------------------------------------------------------------- main
def main():
    ap = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    ap.add_argument("--repo", required=True)
    ap.add_argument("--mode", choices=["commits", "rules", "both"], default="both")
    ap.add_argument("--limit", type=int, default=10, help="most recent N commits")
    ap.add_argument("--live", action="store_true", help="call Jev (needs TYPESAFE_API_KEY in env)")
    ap.add_argument("--validate", action="store_true", help="score Jev against the labeled rule cases")
    ap.add_argument("--out", default=".")
    a = ap.parse_args()

    key = os.environ.get("TYPESAFE_API_KEY", "")
    if a.live and not key:
        sys.exit("--live needs TYPESAFE_API_KEY in your environment (do not commit it).")
    repo, out = Path(a.repo), Path(a.out)
    out.mkdir(parents=True, exist_ok=True)
    report = {"model": MODEL, "live": a.live, "commits": [], "rules": []}

    if a.mode in ("commits", "both"):
        ctx = repo_context(repo)
        shas = git(repo, "log", f"-{a.limit}", "--format=%h").split()
        qs = question_payload(CATALOG["commit"])
        for sha in shas:
            subject, facts, state = build_commit(repo, sha, ctx)
            tok = est_tokens({"state": state, "q": qs})
            if tok > MAX_STATE_TOKENS:
                print(f"[warn] {sha} request ~{tok} tokens, over budget: excerpt should be trimmed")
            resp = call_jev(state, qs, key) if a.live else None
            findings = judge_commit(facts, resp)
            report["commits"].append({"sha": sha, "subject": subject, "est_tokens": tok, "facts": facts,
                                      "answers": {k: v["noul"] for k, v in resp["answers"].items()} if resp else None,
                                      "findings": findings})

    if a.mode in ("rules", "both"):
        spec = find_spec(repo)
        spec_lines = Path(spec).read_text(encoding="utf-8").splitlines() if spec else []
        qs = question_payload(CATALOG["rule"])
        for rule in RULES:
            state = build_rule(repo, rule, spec_lines)
            resp = call_jev(state, qs, key) if a.live else None
            report["rules"].append({"id": rule["id"], "est_tokens": est_tokens({"state": state, "q": qs}),
                                    "answers": {k: v["noul"] for k, v in resp["answers"].items()} if resp else None,
                                    "findings": judge_rule(resp) if resp else []})

    if a.validate and a.live and report["rules"]:
        ok = tot = 0
        for r, rule in zip(report["rules"], RULES):
            for q, exp in rule["expected"].items():
                if exp is None:
                    continue
                tot += 1
                got = r["answers"][q] >= 0.5
                ok += got == exp
                if got != exp:
                    print(f'[validate] miss {rule["id"]}.{q}: p={r["answers"][q]:.2f}, expected {exp}')
        print(f"[validate] {ok}/{tot} labeled answers agree at 0.5. Tune thresholds/criteria before trusting.")
        report["validation"] = {"agree": ok, "total": tot}

    (out / "jev-audit-report.json").write_text(json.dumps(report, indent=2), encoding="utf-8")
    lines = [f'# Jev audit ({"live" if a.live else "DRY RUN, no Jev call"})', ""]
    for c in report["commits"]:
        lines.append(f'## {c["sha"]} {c["subject"]}  (~{c["est_tokens"]} tok)')
        lines += [f'- **{f["severity"]}** `{f["code"]}`: {f["message"]}' for f in c["findings"]] or ["- clean"]
    for r in report["rules"]:
        lines.append(f'## rule {r["id"]}  (~{r["est_tokens"]} tok)')
        lines += [f'- **{f["severity"]}** `{f["code"]}`: {f["message"]}' for f in r["findings"]] or ["- (no findings / not asked in dry run)"]
    (out / "jev-audit-report.md").write_text("\n".join(lines) + "\n", encoding="utf-8")
    print("\n".join(lines))


if __name__ == "__main__":
    main()
