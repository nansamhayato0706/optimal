---
description: Restate requirements, assess risks, and create step-by-step implementation plan. WAIT for user CONFIRM before touching any code.
---

# Plan Command

This command invokes the **planner** agent to create a comprehensive implementation plan before writing any code.

## What This Command Does

1. **Restate Requirements** - Clarify what needs to be built
2. **Identify Risks** - Surface potential issues and blockers
3. **Create Step Plan** - Break down implementation into phases
4. **Wait for Confirmation** - MUST receive user approval before proceeding

## When to Use

Use `/plan` when:
- Starting a new feature
- Making significant architectural changes
- Working on complex refactoring
- Multiple files/components will be affected
- Requirements are unclear or ambiguous

## How It Works

The planner will:

1. **Analyze the request** and restate requirements in clear terms
2. **Break down into phases** with specific, actionable steps:
   - DB schema changes (if any)
   - Repository: new methods in `app/Repositories/`
   - Service: business logic in `app/Services/`
   - Controller: thin handler in `app/Controllers/`
   - View: template in `app/Views/`
   - Routes: registration in `bootstrap/routes.php`
   - Container: DI wiring in `bootstrap/container.php`
3. **Identify dependencies** between components
4. **Assess risks** and potential blockers
5. **Estimate complexity** (High/Medium/Low)
6. **Present the plan** and WAIT for your explicit confirmation

## Architecture Context

This project uses a single-entry-point DI architecture:

```
index.php → bootstrap/app.php → bootstrap/routes.php (RouteRegistry)
  → Controllers (app/Controllers/)
  → Services (app/Services/)
  → Repositories (app/Repositories/)
  → Views (app/Views/)
```

**No `app/EntryPoints/` layer** — unlike the `high_speed` project.

New features must be:
- Registered as DI bindings in `bootstrap/container.php`
- Registered as routes in `bootstrap/routes.php`
- Auth-gated via `$this->auth->require*Route()` at the top of `handle()`

## Example Plan Structure

```
## Implementation Plan: <Feature Name>

### Requirements
- ...

### Phase 1: DB Schema
- ALTER TABLE / CREATE TABLE if needed

### Phase 2: Repository
- app/Repositories/XxxRepository.php — new methods

### Phase 3: Service
- app/Services/XxxService.php — business logic

### Phase 4: Controller
- app/Controllers/XxxController.php — auth check + delegate to service

### Phase 5: View
- app/Views/xxx/index.php — template with $h() escaping

### Phase 6: Wiring
- bootstrap/routes.php — add route
- bootstrap/container.php — add singleton/bind

### Risks
- ...

### Complexity: HIGH / MEDIUM / LOW

**WAITING FOR CONFIRMATION**: Proceed? (yes / modify: ... / different approach: ...)
```

## Important Notes

**CRITICAL**: Do **NOT** write any code until the user explicitly confirms the plan.

If you want changes, respond with:
- `"modify: [your changes]"`
- `"different approach: [alternative]"`
- `"skip phase 2"` etc.

## Integration with Other Commands

After planning:
- `/verify` to check changes after implementation
- `/security-scan` to scan for security issues before commit
- `/code-review` to review completed implementation
