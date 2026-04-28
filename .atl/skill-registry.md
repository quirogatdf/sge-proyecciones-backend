# Skill Registry

## Available Skills

### User Skills (~/.config/opencode/skills/)
- ai-sdk-5: Vercel AI SDK 5 patterns
- angular-architecture: Angular architecture patterns
- angular-core: Angular core patterns (standalone components, signals, inject, control flow, zoneless)
- angular-forms: Angular forms (Signal Forms and Reactive Forms)
- angular-performance: Angular performance optimization
- branch-pr: PR creation workflow for Agent Teams Lite
- django-drf: Django REST Framework patterns
- go-testing: Go testing patterns for Gentleman.Dots
- issue-creation: Issue creation workflow for Agent Teams Lite
- laravel-specialist: Laravel Specialist - Build and configure Laravel 10+ apps
- lucide-angular: Lucide icons for Angular
- php-pro: PHP Pro - Modern PHP 8.3+ patterns
- sdd-apply: Implement tasks from the change
- sdd-archive: Sync delta specs to main specs and archive a completed change
- sdd-design: Create technical design document
- sdd-explore: Explore and investigate ideas before committing to a change
- sdd-init: Initialize Spec-Driven Development context
- sdd-onboard: Guided end-to-end walkthrough of the SDD workflow
- sdd-propose: Create a change proposal with intent, scope, and approach
- sdd-spec: Write specifications with requirements and scenarios
- sdd-tasks: Break down a change into an implementation task checklist
- sdd-verify: Validate that implementation matches specs, design, and tasks
- skill-creator: Creates new AI agent skills
- typescript: TypeScript strict patterns and best practices

### Community Skills (~/.config/opencode/skills/community/)
- community/electron: Electron patterns for desktop apps
- community/elixir-antipatterns: Elixir/Phoenix anti-patterns
- community/hexagonal-architecture-layers-java: Hexagonal architecture for Java
- community/java-21: Java 21 language and runtime patterns
- community/react-native: React Native patterns for mobile apps
- community/spartan-ng: Spartan UI para Angular con Brain y Helm
- community/spring-boot-3: Spring Boot 3 patterns

### Curated Skills (~/.config/opencode/skills/curated/)
- curated/skill-creator: Skill creation patterns
- curated/angular/core: Angular core patterns
- curated/zustand-5: Zustand 5 state management patterns (various sub-skills)

## Project Conventions
No project-specific convention files found (AGENTS.md, CLAUDE.md, etc.)

## Skill Discovery Methodology
Skills are discovered by scanning:
1. User-level: ~/.config/opencode/skills/
2. Project-level: skills/ (none found in this project)

## Usage
When working on a task, the system will automatically load relevant skills based on detected context.
For example:
- Laravel projects will trigger laravel-specialist skill
- TypeScript files will trigger typescript skill
- Testing scenarios will trigger appropriate testing skills