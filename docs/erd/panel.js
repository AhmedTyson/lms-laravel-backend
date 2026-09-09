/* Panel: select, tabs, focus, keyboard. Depends: data.js, graph.js */
/* ============================== SELECT / PANEL ============================== */
let selected=null, focusedTable=null, activeTab='overview';
const panelHead=document.getElementById('panelHead'), panelTabs=document.getElementById('panelTabs'), panelBody=document.getElementById('panelBody');

function genSnippet(t){
  const lines = t.cols.map(c=>{
    let l = `  ${c.n} ${c.t.split('→')[0].trim()}`;
    if(c.nn) l+=' NOT NULL';
    if(c.def) l+=` DEFAULT ${c.def}`;
    if(c.pk) l+=' [PK]';
    if(c.fk) l+=` [FK → ${c.t.split('→')[1]?.trim()||''}]`;
    return l;
  });
  const idx = t.idx.map(i=>`  INDEX (${i.cols.join(', ')})${i.unique?' UNIQUE':''}${i.note?' -- '+i.note:''}`);
  return `Table ${t.name} {\n${lines.join('\n')}\n${idx.length?'\n'+idx.join('\n'):''}\n}`;
}

function select(name){
  selected = name;
  activeTab = 'overview';
  document.querySelectorAll('.tbl').forEach(el=>el.classList.toggle('sel', el.id==='n-'+name));
  render();
}
function render(){
  if(!selected){
    panelHead.innerHTML = `<div class="empty">Click a table to see its full story, columns, indexes and rules.<br><br>Hover any table to trace its relationships on the diagram.</div>`;
    panelTabs.innerHTML=''; panelBody.innerHTML=''; return;
  }
  const t = byName[selected];
  panelHead.innerHTML = `<h2>${t.name}</h2><span class="mchip" style="background:${MODULES[t.module].color}">${MODULES[t.module].label}</span>
    <div class="actionrow">
      <button onclick="enterFocus('${t.name}')">🔎 Focus + neighbors</button>
      <button onclick="copySnippet()">⧉ Copy schema</button>
    </div>`;
  const tabs = [['overview','Overview'],['columns',`Columns (${t.cols.length})`],['indexes',`Indexes (${t.idx.length})`],['relations','Relations'],['rules',`Rules (${t.rules.length})`]];
  panelTabs.innerHTML = tabs.map(([id,label])=>`<button class="tab ${activeTab===id?'on':''}" onclick="setTab('${id}')">${label}</button>`).join('');

  const ins = RELS.filter(e=>e[2]===t.name && e[0]!==t.name).map(e=>`<div class="rule">⤵ <b>${e[0]}.${e[1]}</b> → here <span style="color:var(--mut)">(${e[3]})</span></div>`).join('');
  const outs = RELS.filter(e=>e[0]===t.name && e[2]!==t.name).map(e=>`<div class="rule">⤴ <b>${e[1]}</b> → <b>${e[2]}</b> <span style="color:var(--mut)">(${e[3]}${e[4]?', optional':''})</span></div>`).join('');
  const selfRef = RELS.filter(e=>e[0]===t.name && e[2]===t.name).map(e=>`<div class="rule">↺ <b>${e[1]}</b> → same table <span style="color:var(--mut)">(${e[3]})</span></div>`).join('');

  const panels = {
    overview: `<h4>Business story</h4><p>${t.story}</p>`,
    columns: `<h4>Columns</h4><table>${t.cols.map(c=>`<tr><td class="c">${c.n}${c.pk?' <span class="badge b-pk">PK</span>':''}${c.fk?' <span class="badge b-fk">FK</span>':''}</td><td class="t">${c.t}${c.nn?' · NOT NULL':''}${c.note?' — '+c.note:''}</td><td class="dflt">${c.def?('def '+c.def):''}</td></tr>`).join('')}</table>`,
    indexes: t.idx.length ? `<h4>Indexes</h4>${t.idx.map(i=>`<div class="idxrow"><span class="cols">(${i.cols.join(', ')})</span><span class="note">${i.unique?'UNIQUE':'index'}${i.note?' · '+i.note:''}</span></div>`).join('')}` : `<div class="empty">No secondary indexes beyond the primary key.</div>`,
    relations: (outs+selfRef+ins) || `<div class="empty">No foreign-key relationships.</div>`,
    rules: t.rules.map(r=>`<div class="rule"><b>${r[0]}</b> — ${r[1]}</div>`).join(''),
  };
  panelBody.innerHTML = panels[activeTab];
}
function setTab(id){ activeTab=id; render(); }
function copySnippet(){
  if(!selected) return;
  const txt = genSnippet(byName[selected]);
  navigator.clipboard?.writeText(txt).catch(()=>{});
  const btns = panelHead.querySelectorAll('.actionrow button');
  const b = btns[1]; if(b){ const old=b.textContent; b.textContent='✓ Copied'; setTimeout(()=>b.textContent=old,1200); }
}

/* ============================== FOCUS MODE ============================== */
function enterFocus(name){
  focusedTable = name;
  const keep = relatedSet(name);
  document.querySelectorAll('.tbl').forEach(el=>{
    const n = el.id.slice(2);
    el.style.opacity='';
    el.classList.toggle('dim', !keep.has(n));
  });
  svg.querySelectorAll('[data-a]').forEach(p=>{
    const hot = p.dataset.a===name || p.dataset.b===name;
    p.style.opacity = hot ? '1' : '.06';
  });
  document.getElementById('focusBanner').style.display='flex';
  document.getElementById('focusName').textContent=name;
  select(name);
  fitToNodes([...keep]);
}
function exitFocus(){
  focusedTable=null;
  document.querySelectorAll('.tbl').forEach(el=>{ el.classList.remove('dim'); el.style.opacity=''; });
  svg.querySelectorAll('[data-a]').forEach(p=>p.style.opacity='1');
  document.getElementById('focusBanner').style.display='none';
}

/* ============================== COLLAPSIBLE INFO CARDS ============================== */
function toggleCard(h2){ h2.parentElement.classList.toggle('collapsed'); }

/* ============================== KEYBOARD ============================== */
document.addEventListener('keydown', e=>{
  if(e.key==='/' && document.activeElement.id!=='q'){ e.preventDefault(); document.getElementById('q').focus(); }
  if(e.key==='Escape'){
    document.getElementById('q').value=''; applyFilters();
    selected=null; document.querySelectorAll('.tbl').forEach(el=>el.classList.remove('sel'));
    render();
    if(focusedTable) exitFocus();
  }
});


function initPanel(){ render(); select('users'); }