#!/usr/bin/env python3
"""Deterministic specification generator for LMS Chen ERD & 3NF Relational Mapping."""
import json
from pathlib import Path


def cw(col: str) -> int:
    return max(82, round(7.4 * len(col) + 28))


def table_width(cols: list[tuple[str, str]]) -> int:
    return sum(cw(c) for c, _ in cols)


def build_chen() -> dict:
    entities = [
        {"id": "Group", "label": "Group", "x": 760, "y": 1000, "w": 220, "h": 95, "weak": False},
        {"id": "PermissionGrant", "label": "PermissionGrant", "x": 760, "y": 1950, "w": 290, "h": 95, "weak": False},
        {"id": "ActivityLog", "label": "ActivityLog", "x": 760, "y": 3350, "w": 240, "h": 95, "weak": False},
        {"id": "User", "label": "User", "x": 2150, "y": 1520, "w": 320, "h": 100, "weak": False},
        {"id": "Notification", "label": "Notification", "x": 3100, "y": 480, "w": 240, "h": 95, "weak": True},
        {"id": "Course", "label": "Course", "x": 3950, "y": 880, "w": 240, "h": 95, "weak": False},
        {"id": "Lesson", "label": "Lesson", "x": 5450, "y": 480, "w": 220, "h": 95, "weak": True},
        {"id": "Assignment", "label": "Assignment", "x": 3950, "y": 1680, "w": 250, "h": 95, "weak": False},
        {"id": "Submission", "label": "Submission", "x": 3180, "y": 2120, "w": 240, "h": 95, "weak": True},
        {"id": "Enrollment", "label": "Enrollment", "x": 3950, "y": 2950, "w": 250, "h": 95, "weak": False},
        {"id": "ComponentCompletion", "label": "ComponentCompletion", "x": 2280, "y": 3450, "w": 340, "h": 95, "weak": True},
        {"id": "ProgressRecord", "label": "ProgressRecord", "x": 3950, "y": 3920, "w": 280, "h": 95, "weak": True},
        {"id": "Quiz", "label": "Quiz", "x": 5700, "y": 1680, "w": 220, "h": 95, "weak": False},
        {"id": "Attempt", "label": "Attempt", "x": 5700, "y": 2950, "w": 230, "h": 95, "weak": True},
        {"id": "Question", "label": "Question", "x": 7050, "y": 1680, "w": 230, "h": 95, "weak": True},
        {"id": "QuestionOption", "label": "QuestionOption", "x": 7050, "y": 540, "w": 270, "h": 95, "weak": True},
    ]

    relationships = [
        {"id": "Owns", "label": "Owns", "x": 1440, "y": 1080, "ident": False, "w": 200, "h": 110},
        {"id": "MemberOf", "label": "MemberOf", "x": 1440, "y": 1450, "ident": False, "w": 220, "h": 110},
        {"id": "Grants", "label": "Grants", "x": 1440, "y": 1700, "ident": False, "w": 200, "h": 110},
        {"id": "ReceivesGrant", "label": "ReceivesGrant", "x": 1440, "y": 1950, "ident": False, "w": 240, "h": 115},
        {"id": "ScopedTo", "label": "ScopedTo", "x": 760, "y": 1475, "ident": False, "w": 210, "h": 110},
        {"id": "Manages", "label": "Manages", "x": 1700, "y": 2280, "ident": False, "w": 210, "h": 110},
        {"id": "Notifies", "label": "Notifies", "x": 2760, "y": 1020, "ident": True, "w": 210, "h": 115},
        {"id": "Instructs", "label": "Instructs", "x": 3100, "y": 1180, "ident": False, "w": 210, "h": 110},
        {"id": "HasLesson", "label": "HasLesson", "x": 4700, "y": 680, "ident": True, "w": 220, "h": 115},
        {"id": "HasAssignment", "label": "HasAssignment", "x": 3950, "y": 1280, "ident": False, "w": 240, "h": 115},
        {"id": "HasQuiz", "label": "HasQuiz", "x": 4900, "y": 1240, "ident": False, "w": 210, "h": 110},
        {"id": "ForCourse", "label": "ForCourse", "x": 4760, "y": 1980, "ident": False, "w": 220, "h": 110},
        {"id": "Grades", "label": "Grades", "x": 2650, "y": 1820, "ident": False, "w": 200, "h": 110},
        {"id": "ForAssignment", "label": "ForAssignment", "x": 3560, "y": 1900, "ident": True, "w": 240, "h": 115},
        {"id": "Submits", "label": "Submits", "x": 3560, "y": 2535, "ident": True, "w": 210, "h": 115},
        {"id": "EnrollsIn", "label": "EnrollsIn", "x": 2780, "y": 2560, "ident": False, "w": 210, "h": 110},
        {"id": "Overrides", "label": "Overrides", "x": 2215, "y": 2500, "ident": False, "w": 210, "h": 110},
        {"id": "Completes", "label": "Completes", "x": 3100, "y": 3220, "ident": True, "w": 220, "h": 115},
        {"id": "TracksProgress", "label": "TracksProgress", "x": 3950, "y": 3435, "ident": True, "w": 250, "h": 115},
        {"id": "ForQuiz", "label": "ForQuiz", "x": 5700, "y": 2315, "ident": True, "w": 210, "h": 115},
        {"id": "Sits", "label": "Sits", "x": 4825, "y": 2950, "ident": True, "w": 200, "h": 115},
        {"id": "HasQuestion", "label": "HasQuestion", "x": 6375, "y": 1680, "ident": True, "w": 230, "h": 115},
        {"id": "Answers", "label": "Answers", "x": 6420, "y": 2360, "ident": False, "w": 210, "h": 110},
        {"id": "HasOption", "label": "HasOption", "x": 7050, "y": 1110, "ident": True, "w": 220, "h": 115},
    ]

    anchors = {
        "User": {
            "Owns": [1990, 1485],
            "MemberOf": [1990, 1510],
            "Grants": [1990, 1535],
            "ReceivesGrant": [1990, 1560],
            "Manages": [2030, 1570],
            "Overrides": [2130, 1570],
            "EnrollsIn": [2220, 1570],
            "Grades": [2310, 1555],
            "Instructs": [2310, 1510],
            "Notifies": [2260, 1470],
        },
        "Course": {
            "Instructs": [3830, 895],
            "HasLesson": [4070, 855],
            "HasQuiz": [4070, 905],
            "ForCourse": [4040, 927],
            "HasAssignment": [3950, 927],
        },
        "Enrollment": {
            "EnrollsIn": [3825, 2930],
            "Submits": [3825, 2910],
            "Completes": [3825, 2965],
            "TracksProgress": [3950, 2997],
            "Sits": [4075, 2950],
            "ForCourse": [4040, 2902],
        },
    }

    links = [
        # Access Management
        {"a": "User", "b": "Owns", "card": "1", "ent": "a", "total": False, "off": 24},
        {"a": "Owns", "b": "Group", "card": "N", "ent": "b", "total": True, "off": 24},
        {"a": "User", "b": "MemberOf", "card": "M", "ent": "a", "total": False, "off": 24},
        {"a": "MemberOf", "b": "Group", "card": "N", "ent": "b", "total": False, "off": 24},
        {"a": "User", "b": "Grants", "card": "1", "ent": "a", "total": False, "off": 24},
        {"a": "Grants", "b": "PermissionGrant", "card": "N", "ent": "b", "total": True, "off": 24},
        {"a": "User", "b": "ReceivesGrant", "card": "1", "ent": "a", "total": False, "off": 24},
        {"a": "ReceivesGrant", "b": "PermissionGrant", "card": "N", "ent": "b", "total": True, "off": 24},
        {"a": "Group", "b": "ScopedTo", "card": "1", "ent": "a", "total": False, "off": 26},
        {"a": "ScopedTo", "b": "PermissionGrant", "card": "N", "ent": "b", "total": False, "off": 26},
        # Unary Manages (1 manager -> N subordinates)
        {"a": "User", "b": "Manages", "card": "1", "ent": "a", "total": False, "off": -34},
        {"a": "User", "b": "Manages", "card": "N", "ent": "b", "total": False, "off": 34},
        # Notifications
        {"a": "User", "b": "Notifies", "card": "1", "ent": "a", "total": False, "off": 24},
        {"a": "Notifies", "b": "Notification", "card": "N", "ent": "b", "total": True, "off": 24},
        # Courses & Content
        {"a": "User", "b": "Instructs", "card": "1", "ent": "a", "total": False, "off": 24},
        {"a": "Instructs", "b": "Course", "card": "N", "ent": "b", "total": True, "off": 24},
        {"a": "Course", "b": "HasLesson", "card": "1", "ent": "a", "total": False, "off": 24},
        {"a": "HasLesson", "b": "Lesson", "card": "N", "ent": "b", "total": True, "off": 24},
        {"a": "Course", "b": "HasAssignment", "card": "1", "ent": "a", "total": False, "off": 26},
        {"a": "HasAssignment", "b": "Assignment", "card": "N", "ent": "b", "total": True, "off": 26},
        {"a": "Course", "b": "HasQuiz", "card": "1", "ent": "a", "total": False, "off": 24},
        {"a": "HasQuiz", "b": "Quiz", "card": "N", "ent": "b", "total": True, "off": 24},
        # Enrollment
        {"a": "User", "b": "EnrollsIn", "card": "1", "ent": "a", "total": False, "off": 24},
        {"a": "EnrollsIn", "b": "Enrollment", "card": "N", "ent": "b", "total": True, "off": 24},
        {"a": "Course", "b": "ForCourse", "card": "1", "ent": "a", "total": False, "off": 24},
        {"a": "ForCourse", "b": "Enrollment", "card": "N", "ent": "b", "total": True, "off": 24},
        # Submissions
        {"a": "User", "b": "Grades", "card": "1", "ent": "a", "total": False, "off": 24},
        {"a": "Grades", "b": "Submission", "card": "N", "ent": "b", "total": False, "off": 24},
        {"a": "Assignment", "b": "ForAssignment", "card": "1", "ent": "a", "total": False, "off": 24},
        {"a": "ForAssignment", "b": "Submission", "card": "N", "ent": "b", "total": True, "off": 24},
        {"a": "Enrollment", "b": "Submits", "card": "1", "ent": "a", "total": False, "off": 24},
        {"a": "Submits", "b": "Submission", "card": "N", "ent": "b", "total": True, "off": 24},
        # Quizzes, Questions, Options, Attempts
        {"a": "Quiz", "b": "HasQuestion", "card": "1", "ent": "a", "total": False, "off": 24},
        {"a": "HasQuestion", "b": "Question", "card": "N", "ent": "b", "total": True, "off": 24},
        {"a": "Question", "b": "HasOption", "card": "1", "ent": "a", "total": False, "off": 26},
        {"a": "HasOption", "b": "QuestionOption", "card": "N", "ent": "b", "total": True, "off": 26},
        {"a": "Quiz", "b": "ForQuiz", "card": "1", "ent": "a", "total": False, "off": 26},
        {"a": "ForQuiz", "b": "Attempt", "card": "N", "ent": "b", "total": True, "off": 26},
        {"a": "Enrollment", "b": "Sits", "card": "1", "ent": "a", "total": False, "off": 24},
        {"a": "Sits", "b": "Attempt", "card": "N", "ent": "b", "total": True, "off": 24},
        {"a": "Attempt", "b": "Answers", "card": "M", "ent": "a", "total": False, "off": 24},
        {"a": "Answers", "b": "Question", "card": "N", "ent": "b", "total": False, "off": 24},
        # Progress & Completions
        {"a": "Enrollment", "b": "TracksProgress", "card": "1", "ent": "a", "total": True, "off": 26},
        {"a": "TracksProgress", "b": "ProgressRecord", "card": "1", "ent": "b", "total": True, "off": 26},
        {"a": "Enrollment", "b": "Completes", "card": "1", "ent": "a", "total": False, "off": 24},
        {"a": "Completes", "b": "ComponentCompletion", "card": "N", "ent": "b", "total": True, "off": 24},
        {"a": "User", "b": "Overrides", "card": "1", "ent": "a", "total": False, "off": 26},
        {"a": "Overrides", "b": "ComponentCompletion", "card": "N", "ent": "b", "total": False, "off": 26},
    ]

    attribute_groups = [
        {
            "owner": "User",
            "layout": "fan",
            "a0": 212,
            "a1": 296,
            "radii": [340, 520, 700],
            "items": [
                {"label": "id", "kind": "key"},
                {"label": "name", "kind": "simple"},
                {"label": "email_verified_at", "kind": "simple"},
                {"label": "email", "kind": "simple"},
                {"label": "phone_number", "kind": "simple"},
                {"label": "remember_token", "kind": "simple"},
                {"label": "password", "kind": "simple"},
                {"label": "approval_status", "kind": "simple"},
                {"label": "manager_depth", "kind": "derived"},
            ],
        },
        {
            "owner": "Group",
            "layout": "fan",
            "a0": 175,
            "a1": 245,
            "radii": [290, 340],
            "items": [
                {"label": "id", "kind": "key"},
                {"label": "name", "kind": "simple"},
            ],
        },
        {
            "owner": "MemberOf",
            "layout": "polar",
            "items": [
                {"label": "joined_at", "kind": "simple", "ang": 270, "r": 145},
            ],
        },
        {
            "owner": "PermissionGrant",
            "layout": "fan",
            "a0": 125,
            "a1": 235,
            "radii": [330, 500],
            "items": [
                {"label": "id", "kind": "key"},
                {"label": "permission_name", "kind": "simple"},
                {"label": "action", "kind": "simple"},
                {"label": "granted_at", "kind": "simple"},
                {"label": "revoked_at", "kind": "simple"},
                {"label": "created_at", "kind": "simple"},
            ],
        },
        {
            "owner": "ActivityLog",
            "layout": "fan",
            "a0": 15,
            "a1": 330,
            "radii": [320, 500],
            "items": [
                {"label": "id", "kind": "key"},
                {"label": "log_name", "kind": "simple"},
                {"label": "description", "kind": "simple"},
                {"label": "subject_type", "kind": "simple"},
                {"label": "subject_id", "kind": "simple"},
                {"label": "event", "kind": "simple"},
                {"label": "causer_type", "kind": "simple"},
                {"label": "causer_id", "kind": "simple"},
                {"label": "attribute_changes", "kind": "simple"},
                {"label": "properties", "kind": "simple"},
                {"label": "created_at", "kind": "simple"},
            ],
        },
        {
            "owner": "Notification",
            "layout": "fan",
            "a0": 215,
            "a1": 350,
            "radii": [260, 410],
            "items": [
                {"label": "id", "kind": "key"},
                {"label": "type", "kind": "simple"},
                {"label": "data", "kind": "simple"},
                {"label": "read_at", "kind": "simple"},
                {"label": "created_at", "kind": "simple"},
            ],
        },
        {
            "owner": "Course",
            "layout": "fan",
            "a0": 215,
            "a1": 315,
            "radii": [310, 480],
            "items": [
                {"label": "id", "kind": "key"},
                {"label": "title", "kind": "simple"},
                {"label": "category", "kind": "simple"},
                {"label": "description", "kind": "simple"},
                {"label": "status", "kind": "simple"},
                {"label": "published_at", "kind": "simple"},
                {"label": "archived_at", "kind": "simple"},
            ],
        },
        {
            "owner": "Lesson",
            "layout": "fan",
            "a0": 235,
            "a1": 360,
            "radii": [280, 430],
            "items": [
                {"label": "order", "kind": "partial"},
                {"label": "id", "kind": "simple"},
                {"label": "title", "kind": "simple"},
                {"label": "content_reference", "kind": "simple"},
            ],
        },
        {
            "owner": "Assignment",
            "layout": "fan",
            "a0": 45,
            "a1": 122,
            "radii": [300, 480, 650],
            "items": [
                {"label": "id", "kind": "key"},
                {"label": "title", "kind": "simple"},
                {"label": "resubmission_allowed", "kind": "simple"},
                {"label": "due_date", "kind": "simple"},
                {"label": "description", "kind": "simple"},
                {"label": "passing_threshold", "kind": "simple"},
                {"label": "max_score", "kind": "simple"},
            ],
        },
        {
            "owner": "Submission",
            "layout": "fan",
            "a0": 236,
            "a1": 310,
            "radii": [260, 440],
            "items": [
                {"label": "attempt_number", "kind": "partial"},
                {"label": "id", "kind": "simple"},
                {"label": "status", "kind": "simple"},
                {"label": "is_late", "kind": "simple"},
                {"label": "grade", "kind": "simple"},
            ],
        },
        {
            "owner": "Submission",
            "layout": "fan",
            "a0": 108,
            "a1": 168,
            "radii": [260, 420],
            "items": [
                {"label": "file_path", "kind": "simple"},
                {"label": "content", "kind": "simple"},
                {"label": "submitted_at", "kind": "simple"},
                {"label": "graded_at", "kind": "simple"},
            ],
        },
        {
            "owner": "Enrollment",
            "layout": "fan",
            "a0": 15,
            "a1": 52,
            "radii": [290, 420],
            "items": [
                {"label": "id", "kind": "key"},
                {"label": "status", "kind": "simple"},
                {"label": "enrolled_at", "kind": "simple"},
                {"label": "completed_at", "kind": "simple"},
            ],
        },
        {
            "owner": "ProgressRecord",
            "layout": "fan",
            "a0": 30,
            "a1": 150,
            "radii": [260, 400],
            "items": [
                {"label": "id", "kind": "key"},
                {"label": "percent_complete", "kind": "simple"},
                {"label": "completed_at", "kind": "simple"},
                {"label": "updated_at", "kind": "simple"},
            ],
        },
        {
            "owner": "ComponentCompletion",
            "layout": "fan",
            "a0": 135,
            "a1": 235,
            "radii": [300, 460],
            "items": [
                {"label": "component_type", "kind": "partial"},
                {"label": "component_id", "kind": "partial"},
                {"label": "id", "kind": "simple"},
                {"label": "completed_at", "kind": "simple"},
                {"label": "is_override", "kind": "simple"},
            ],
        },
        {
            "owner": "Quiz",
            "layout": "fan",
            "a0": 225,
            "a1": 342,
            "radii": [300, 490],
            "items": [
                {"label": "id", "kind": "key"},
                {"label": "title", "kind": "simple"},
                {"label": "opens_at", "kind": "simple"},
                {"label": "closes_at", "kind": "simple"},
                {"label": "max_attempts", "kind": "simple"},
                {"label": "passing_threshold", "kind": "simple"},
                {"label": "total_points", "kind": "derived"},
            ],
        },
        {
            "owner": "Attempt",
            "layout": "fan",
            "a0": 35,
            "a1": 145,
            "radii": [280, 430],
            "items": [
                {"label": "attempt_number", "kind": "partial"},
                {"label": "id", "kind": "simple"},
                {"label": "score", "kind": "simple"},
                {"label": "started_at", "kind": "simple"},
                {"label": "submitted_at", "kind": "simple"},
            ],
        },
        {
            "owner": "Answers",
            "layout": "fan",
            "a0": 25,
            "a1": 115,
            "radii": [260, 410],
            "items": [
                {"label": "selected_option_ids", "kind": "simple"},
                {"label": "answer_boolean", "kind": "simple"},
                {"label": "answer_text", "kind": "simple"},
                {"label": "answer_number", "kind": "simple"},
                {"label": "is_correct", "kind": "simple"},
            ],
        },
        {
            "owner": "Question",
            "layout": "fan",
            "a0": -55,
            "a1": 65,
            "radii": [350, 540],
            "items": [
                {"label": "order", "kind": "partial"},
                {"label": "id", "kind": "simple"},
                {"label": "prompt", "kind": "simple"},
                {"label": "type", "kind": "simple"},
                {"label": "points", "kind": "simple"},
                {"label": "correct_boolean", "kind": "simple"},
                {"label": "correct_number", "kind": "simple"},
                {"label": "numeric_tolerance", "kind": "simple"},
                {"label": "AcceptedAnswer", "kind": "multi"},
            ],
        },
        {
            "owner": "QuestionOption",
            "layout": "fan",
            "a0": 200,
            "a1": 340,
            "radii": [270, 410],
            "items": [
                {"label": "order", "kind": "partial"},
                {"label": "id", "kind": "simple"},
                {"label": "label", "kind": "simple"},
                {"label": "is_correct", "kind": "simple"},
            ],
        },
    ]

    return {
        "title": "LMSDB (Laravel v2.1) - Chen Entity-Relationship Diagram",
        "width": 7900,
        "height": 4600,
        "legend_pos": [6850, 3980],
        "entities": entities,
        "relationships": relationships,
        "anchors": anchors,
        "links": links,
        "attribute_groups": attribute_groups,
    }


def build_mapping() -> dict:
    # Exact columns matching database/migrations/* and Modules/*/database/migrations/*
    raw_tiers = [
        # Tier 1: y = 320 (leaves y=120..250 completely below title/subtitle y=55..88)
        (320, [
            ("users", [
                ("id", "PK"), ("name", ""), ("email", "UQ"), ("phone_number", ""),
                ("email_verified_at", ""), ("password", ""), ("remember_token", ""),
                ("manager_id", "FK"), ("manager_depth", ""), ("approval_status", ""),
                ("created_at", ""), ("updated_at", ""),
            ]),
            ("activity_log", [
                ("id", "PK"), ("log_name", ""), ("description", ""),
                ("subject_type", ""), ("subject_id", ""), ("event", ""),
                ("causer_type", ""), ("causer_id", ""), ("attribute_changes", ""),
                ("properties", ""), ("created_at", ""), ("updated_at", ""),
            ]),
            ("notifications", [
                ("id", "PK"), ("user_id", "FK"), ("type", ""),
                ("data", ""), ("read_at", ""), ("created_at", ""),
            ]),
        ]),
        # Tier 2: y = 860
        (860, [
            ("groups", [
                ("id", "PK"), ("owner_id", "FK"), ("name", ""),
                ("created_at", ""), ("updated_at", ""),
            ]),
            ("permission_grants", [
                ("id", "PK"), ("granter_id", "FK"), ("grantee_id", "FK"),
                ("group_id", "FK"), ("permission_name", ""), ("action", ""),
                ("granted_at", ""), ("revoked_at", ""), ("created_at", ""),
            ]),
            ("courses", [
                ("id", "PK"), ("instructor_id", "FK,UQ"), ("title", "UQ"),
                ("category", ""), ("description", ""), ("status", ""),
                ("published_at", ""), ("archived_at", ""), ("created_at", ""), ("updated_at", ""),
            ]),
        ]),
        # Tier 3: y = 1440
        (1440, [
            ("group_members", [
                ("id", "PK"), ("group_id", "FK,UQ"), ("user_id", "FK,UQ"), ("joined_at", ""),
            ]),
            ("user_permissions", [
                ("id", "PK"), ("user_id", "FK,UQ"), ("permission_name", "UQ"),
                ("group_id", "FK,UQ"), ("granted_via_grant_id", "FK"),
                ("created_at", ""), ("updated_at", ""),
            ]),
            ("lessons", [
                ("id", "PK"), ("course_id", "FK,UQ"), ("title", ""),
                ("content_reference", ""), ("order", "UQ"),
                ("created_at", ""), ("updated_at", ""),
            ]),
            ("enrollments", [
                ("id", "PK"), ("student_id", "FK,UQ"), ("course_id", "FK,UQ"),
                ("status", ""), ("enrolled_at", ""), ("completed_at", ""),
            ]),
            ("assignments", [
                ("id", "PK"), ("course_id", "FK"), ("title", ""), ("description", ""),
                ("due_date", ""), ("resubmission_allowed", ""),
                ("max_score", ""), ("passing_threshold", ""),
                ("created_at", ""), ("updated_at", ""),
            ]),
            ("quizzes", [
                ("id", "PK"), ("course_id", "FK"), ("title", ""),
                ("opens_at", ""), ("closes_at", ""), ("max_attempts", ""),
                ("passing_threshold", ""), ("created_at", ""), ("updated_at", ""),
            ]),
        ]),
        # Tier 4: y = 2060
        (2060, [
            ("progress_records", [
                ("id", "PK"), ("enrollment_id", "FK,UQ"), ("percent_complete", ""),
                ("completed_at", ""), ("updated_at", ""),
            ]),
            ("component_completions", [
                ("id", "PK"), ("enrollment_id", "FK,UQ"), ("component_type", "UQ"),
                ("component_id", "UQ"), ("completed_at", ""), ("is_override", ""),
                ("overridden_by", "FK"), ("created_at", ""), ("updated_at", ""),
            ]),
            ("submissions", [
                ("id", "PK"), ("assignment_id", "FK,UQ"), ("enrollment_id", "FK,UQ"),
                ("attempt_number", "UQ"), ("file_path", ""), ("content", ""),
                ("submitted_at", ""), ("is_late", ""), ("grade", ""),
                ("graded_by", "FK"), ("graded_at", ""), ("status", ""),
                ("created_at", ""), ("updated_at", ""),
            ]),
            ("attempts", [
                ("id", "PK"), ("quiz_id", "FK,UQ"), ("enrollment_id", "FK,UQ"),
                ("attempt_number", "UQ"), ("score", ""), ("started_at", ""),
                ("submitted_at", ""), ("created_at", ""), ("updated_at", ""),
            ]),
            ("questions", [
                ("id", "PK"), ("quiz_id", "FK"), ("prompt", ""), ("type", ""),
                ("order", ""), ("points", ""), ("correct_boolean", ""),
                ("correct_number", ""), ("numeric_tolerance", ""),
                ("created_at", ""), ("updated_at", ""),
            ]),
        ]),
        # Tier 5: y = 2680
        (2680, [
            ("question_options", [
                ("id", "PK"), ("question_id", "FK"), ("label", ""),
                ("is_correct", ""), ("order", ""), ("created_at", ""), ("updated_at", ""),
            ]),
            ("question_accepted_answers", [
                ("id", "PK"), ("question_id", "FK"), ("answer_text", ""),
                ("created_at", ""), ("updated_at", ""),
            ]),
            ("attempt_answers", [
                ("id", "PK"), ("attempt_id", "FK,UQ"), ("question_id", "FK,UQ"),
                ("selected_option_ids", ""), ("answer_boolean", ""),
                ("answer_text", ""), ("answer_number", ""), ("is_correct", ""),
                ("created_at", ""), ("updated_at", ""),
            ]),
        ]),
    ]

    tables = []
    # Leave x = 40..320 clear for left vertical bus lanes; gap_x = 200 between tables
    for y, items in raw_tiers:
        cur_x = 340
        for name, cols in items:
            tables.append({
                "name": name,
                "x": cur_x,
                "y": y,
                "columns": [{"name": c, "flags": fl} for c, fl in cols],
            })
            cur_x += table_width(cols) + 200

    # Check total width across tiers
    max_right = max(t["x"] + table_width([(c["name"], c["flags"]) for c in t["columns"]]) for t in tables)
    canvas_w = max(6200, max_right + 280)
    canvas_h = 3240

    # Assign 30 FK routes with unique corridor_y, target_corridor_y, bus_x, and target to_off
    fks = [
        # --- Arrivals to users.id (top & bot) ---
        # 1. Self-FK users.manager_id -> users.id (top band)
        {"from_table": "users", "from_col": "manager_id", "from_side": "top",
         "to_table": "users", "to_col": "id", "to_side": "top", "to_off": 41, "corridor_y": 230},
        # 2. notifications.user_id -> users.id (top band)
        {"from_table": "notifications", "from_col": "user_id", "from_side": "top",
         "to_table": "users", "to_col": "id", "to_side": "top", "to_off": 68, "corridor_y": 210},

        # Tier 2 -> users.id (bot, corridor_y in 420..540)
        # 3. groups.owner_id -> users.id
        {"from_table": "groups", "from_col": "owner_id", "from_side": "top",
         "to_table": "users", "to_col": "id", "to_side": "bot", "to_off": 12, "corridor_y": 540},
        # 4. permission_grants.granter_id -> users.id
        {"from_table": "permission_grants", "from_col": "granter_id", "from_side": "top",
         "to_table": "users", "to_col": "id", "to_side": "bot", "to_off": 24, "corridor_y": 520},
        # 5. permission_grants.grantee_id -> users.id
        {"from_table": "permission_grants", "from_col": "grantee_id", "from_side": "top",
         "to_table": "users", "to_col": "id", "to_side": "bot", "to_off": 36, "corridor_y": 500},
        # 6. courses.instructor_id -> users.id
        {"from_table": "courses", "from_col": "instructor_id", "from_side": "top",
         "to_table": "users", "to_col": "id", "to_side": "bot", "to_off": 48, "corridor_y": 480},

        # Tier 3/4 -> users.id via LEFT vertical bus (x=160..280) into users.id top/bot
        # 7. group_members.user_id -> users.id
        {"from_table": "group_members", "from_col": "user_id", "from_side": "top",
         "to_table": "users", "to_col": "id", "to_side": "bot", "to_off": 60,
         "corridor_y": 1240, "bus_x": 280, "target_corridor_y": 460},
        # 8. user_permissions.user_id -> users.id
        {"from_table": "user_permissions", "from_col": "user_id", "from_side": "top",
         "to_table": "users", "to_col": "id", "to_side": "bot", "to_off": 72,
         "corridor_y": 1220, "bus_x": 250, "target_corridor_y": 440},
        # 9. enrollments.student_id -> users.id
        {"from_table": "enrollments", "from_col": "student_id", "from_side": "top",
         "to_table": "users", "to_col": "id", "to_side": "top", "to_off": 14,
         "corridor_y": 1200, "bus_x": 220, "target_corridor_y": 190},
        # 10. component_completions.overridden_by -> users.id
        {"from_table": "component_completions", "from_col": "overridden_by", "from_side": "top",
         "to_table": "users", "to_col": "id", "to_side": "top", "to_off": 26,
         "corridor_y": 1840, "bus_x": 190, "target_corridor_y": 170},
        # 11. submissions.graded_by -> users.id
        {"from_table": "submissions", "from_col": "graded_by", "from_side": "top",
         "to_table": "users", "to_col": "id", "to_side": "top", "to_off": 55,
         "corridor_y": 1820, "bus_x": 160, "target_corridor_y": 150},

        # --- Arrivals to groups.id (Tier 2, y=860..900) ---
        # 12. permission_grants.group_id -> groups.id (same tier: route via top band of Tier 2 y=760)
        {"from_table": "permission_grants", "from_col": "group_id", "from_side": "top",
         "to_table": "groups", "to_col": "id", "to_side": "top", "to_off": 41, "corridor_y": 760},
        # 13. group_members.group_id -> groups.id (Tier 3 -> Tier 2 bot)
        {"from_table": "group_members", "from_col": "group_id", "from_side": "top",
         "to_table": "groups", "to_col": "id", "to_side": "bot", "to_off": 24, "corridor_y": 1060},
        # 14. user_permissions.group_id -> groups.id (Tier 3 -> Tier 2 bot)
        {"from_table": "user_permissions", "from_col": "group_id", "from_side": "top",
         "to_table": "groups", "to_col": "id", "to_side": "bot", "to_off": 56, "corridor_y": 1040},

        # --- Arrival to permission_grants.id (Tier 2, y=860..900) ---
        # 15. user_permissions.granted_via_grant_id -> permission_grants.id
        {"from_table": "user_permissions", "from_col": "granted_via_grant_id", "from_side": "top",
         "to_table": "permission_grants", "to_col": "id", "to_side": "bot", "to_off": 41, "corridor_y": 1020},

        # --- Arrivals to courses.id (Tier 2, y=860..900) ---
        # 16. lessons.course_id -> courses.id
        {"from_table": "lessons", "from_col": "course_id", "from_side": "top",
         "to_table": "courses", "to_col": "id", "to_side": "bot", "to_off": 14, "corridor_y": 1080},
        # 17. enrollments.course_id -> courses.id
        {"from_table": "enrollments", "from_col": "course_id", "from_side": "top",
         "to_table": "courses", "to_col": "id", "to_side": "bot", "to_off": 32, "corridor_y": 1100},
        # 18. assignments.course_id -> courses.id
        {"from_table": "assignments", "from_col": "course_id", "from_side": "top",
         "to_table": "courses", "to_col": "id", "to_side": "bot", "to_off": 50, "corridor_y": 1120},
        # 19. quizzes.course_id -> courses.id
        {"from_table": "quizzes", "from_col": "course_id", "from_side": "top",
         "to_table": "courses", "to_col": "id", "to_side": "bot", "to_off": 68, "corridor_y": 1140},

        # --- Arrivals to enrollments.id (Tier 3, y=1440..1480) ---
        # 20. progress_records.enrollment_id -> enrollments.id
        {"from_table": "progress_records", "from_col": "enrollment_id", "from_side": "top",
         "to_table": "enrollments", "to_col": "id", "to_side": "bot", "to_off": 14, "corridor_y": 1700},
        # 21. component_completions.enrollment_id -> enrollments.id
        {"from_table": "component_completions", "from_col": "enrollment_id", "from_side": "top",
         "to_table": "enrollments", "to_col": "id", "to_side": "bot", "to_off": 32, "corridor_y": 1680},
        # 22. submissions.enrollment_id -> enrollments.id
        {"from_table": "submissions", "from_col": "enrollment_id", "from_side": "top",
         "to_table": "enrollments", "to_col": "id", "to_side": "bot", "to_off": 50, "corridor_y": 1660},
        # 23. attempts.enrollment_id -> enrollments.id
        {"from_table": "attempts", "from_col": "enrollment_id", "from_side": "top",
         "to_table": "enrollments", "to_col": "id", "to_side": "bot", "to_off": 68, "corridor_y": 1640},

        # --- Arrival to assignments.id (Tier 3) ---
        # 24. submissions.assignment_id -> assignments.id
        {"from_table": "submissions", "from_col": "assignment_id", "from_side": "top",
         "to_table": "assignments", "to_col": "id", "to_side": "bot", "to_off": 41, "corridor_y": 1720},

        # --- Arrivals to quizzes.id (Tier 3) ---
        # 25. attempts.quiz_id -> quizzes.id
        {"from_table": "attempts", "from_col": "quiz_id", "from_side": "top",
         "to_table": "quizzes", "to_col": "id", "to_side": "bot", "to_off": 24, "corridor_y": 1740},
        # 26. questions.quiz_id -> quizzes.id
        {"from_table": "questions", "from_col": "quiz_id", "from_side": "top",
         "to_table": "quizzes", "to_col": "id", "to_side": "bot", "to_off": 58, "corridor_y": 1760},

        # --- Arrivals to questions.id (Tier 4, y=2060..2100) ---
        # 27. question_options.question_id -> questions.id
        {"from_table": "question_options", "from_col": "question_id", "from_side": "top",
         "to_table": "questions", "to_col": "id", "to_side": "bot", "to_off": 20, "corridor_y": 2320},
        # 28. question_accepted_answers.question_id -> questions.id
        {"from_table": "question_accepted_answers", "from_col": "question_id", "from_side": "top",
         "to_table": "questions", "to_col": "id", "to_side": "bot", "to_off": 42, "corridor_y": 2340},
        # 29. attempt_answers.question_id -> questions.id
        {"from_table": "attempt_answers", "from_col": "question_id", "from_side": "top",
         "to_table": "questions", "to_col": "id", "to_side": "bot", "to_off": 64, "corridor_y": 2360},

        # --- Arrival to attempts.id (Tier 4) ---
        # 30. attempt_answers.attempt_id -> attempts.id
        {"from_table": "attempt_answers", "from_col": "attempt_id", "from_side": "top",
         "to_table": "attempts", "to_col": "id", "to_side": "bot", "to_off": 41, "corridor_y": 2300},
    ]

    # Put legend in bottom-right open area below Tier 4 right tables, after Tier 5 ends
    t5_right = max(
        t["x"] + table_width([(c["name"], c["flags"]) for c in t["columns"]])
        for t in tables if t["y"] == 2680
    )
    legend_x = max(t5_right + 160, canvas_w - 1220)
    legend_y = 2560

    return {
        "title": "LMSDB (Laravel v2.1) — Relational Schema Mapping (20 Domain Tables)",
        "subtitle": (
            "Normalized (3NF) • ARCH-005 Auto-Increment PKs • ADR-011/012/013/014 Hardened • "
            "Append-Only permission_grants Ledger • Polymorphic component_completions"
        ),
        "footer": "LMS Backend v2.1 • 20 Domain Tables • 30 Explicit Foreign Keys • 3NF/BCNF Verified • Zero-Collision Corridor Routing",
        "width": canvas_w,
        "height": canvas_h,
        "legend_pos": [legend_x, legend_y],
        "tables": tables,
        "fks": fks,
    }


def main():
    spec = {
        "prefix": "LMS",
        "chen": build_chen(),
        "mapping": build_mapping(),
    }
    out_path = Path(__file__).resolve().parent / "spec.json"
    out_path.write_text(json.dumps(spec, indent=2), encoding="utf-8")
    print(f"Wrote {out_path} ({len(spec['chen']['entities'])} entities, {len(spec['mapping']['tables'])} tables, {len(spec['mapping']['fks'])} FKs)")


if __name__ == "__main__":
    main()
