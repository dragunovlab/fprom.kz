## Rules
- Commit BEFORE every change: `git add . && git commit -m "..."`. Then make the change.
- Tag meaningful states: `git tag v<VERSION>-<description>`
- After each change: update AGENTS.md if workflow changed.
- Token is in conversation history — never commit, never log.
- Daily DB backup runs at 03:00 via Windows scheduler (fpromDBBackup).
- Before editing DB: always backup the affected table(s).
- FTP: 185.98.5.112, user: script, pass: Nf7-X2p-STR-ADc
