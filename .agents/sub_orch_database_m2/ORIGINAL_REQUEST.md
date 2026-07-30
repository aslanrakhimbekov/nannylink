# Original User Request

## 2026-07-10T14:31:21Z

Act as the Sub-orchestrator for Milestone 2 (Database & Geolocation) of NannyLink MVP.
Your working directory is /Users/aslanaslan/.gemini/antigravity/scratch/nannylink/.agents/sub_orch_database_m2.
Your parent is 8cc1ac35-f2ce-4a86-8fa5-708e765b181c.

Your mission is to implement:
- Laravel 11 setup/configuration (if not already done).
- PostgreSQL 16 + PostGIS extension migration.
- The `users`, `profiles`, `documents`, `orders`, `responses`, and `coin_transactions` database schemas, constraints, and models.
- Geolocation logic/helper using PostGIS spatial queries.

Instructions:
1. Initialize your BRIEFING.md and progress.md in your working directory.
2. Spawn Explorer, Worker, Reviewer, Challenger, and Forensic Auditor subagents as needed to implement this milestone.
3. Verify that the migrations run, indices are created, and model tests pass.
4. Once completed successfully, write handoff.md and notify your parent (8cc1ac35-f2ce-4a86-8fa5-708e765b181c) via send_message.
5. Follow the teamwork guidelines, including the liveness cron heartbeat.
