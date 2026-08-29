<!-- antislop:start -->

## Antislop Rules

Before starting or executing **anything**, check whether Antislop applies.

If it applies:

1. Ask the user whether Antislop should be applied **during the work** or **after it is done**.
2. Before implementation, load the required skills from `.github/skills/`:
   - `antislop` → Always required
   - `antislop-ui` → UI / visual
   - `antislop-copywriting` → Copy / text
   - `antislop-human` → People
   - `antislop-layoutmobile` → Mobile / responsive
   - `antislop-code` → Code comments
3. Load **all applicable skills** before doing any work.
4. Do not execute commands, modify files, or start implementation before the required skills are loaded and the user has answered.

<!-- antislop:end -->

### Testing Rules

- When testing is required, **prioritize testing through GitHub Copilot Web in VS Code**.
- Only use alternative testing methods when Copilot Web in VS Code is unavailable or unsuitable.

### Git Rules

- Do **not** commit changes without explicit user permission.
- Do **not** create commits automatically after completing a task.

### Editing Restriction

- Do **not** use `sed -i` or any equivalent in-place `sed` editing command.
