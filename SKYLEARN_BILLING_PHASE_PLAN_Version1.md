# Skylearn Billing Pro – Detailed Phase Plan

---

## Phase 1: Project Bootstrapping

### Tasks:
- [ ] Initialize repo and commit main plugin file (`skylearn-billing.php`), README.md, LICENSE, uninstall.php.
- [ ] Set up folder structure as per `skylearn-billing-structure.txt`.
- [ ] Configure basic autoloading for classes.
- [ ] Establish code style guide and commit hooks.

### Acceptance Criteria:
- All core files and directories are present.
- Autoloading works for all classes in `includes/`.
- README outlines project goals and initial structure.
- LICENSE is appropriate for your intended distribution.
- Commit hooks (e.g., PHP_CodeSniffer) are active.

---

## Phase 2: Core Infrastructure

### Tasks:
- [ ] Implement plugin activation/deactivation hooks.
- [ ] Implement uninstall logic (clean database/options).
- [ ] Create initial settings page (admin menu, “General” tab).
- [ ] Set up localization infrastructure (`languages/` folder, .pot file).

### Acceptance Criteria:
- Plugin activates/deactivates/uninstalls cleanly.
- General settings page accessible from WP admin (“Skylearn Billing” sidebar item).
- Settings saved and retrieved correctly.
- .pot file generated; strings are translation-ready.

**UI Notes:**  
- Sidebar menu and tabbed navigation should follow StoreEngine style (see StoreEngine “Settings” and “Addons” screens).
- Use WordPress Settings API for best practices.

---

## Phase 3: Licensing & Tier Management

### Tasks:
- [ ] Develop licensing manager (class) for Free, Pro, Pro Plus.
- [ ] Feature flags system for conditional logic.
- [ ] License key entry/validation page in admin.
- [ ] Tier badge and upgrade prompts in the admin UI.
- [ ] Integrate tier checks into settings/admin UI elements (disable/grey out unavailable features).

### Acceptance Criteria:
- License manager class supports feature flags and checks current tier.
- Admin can enter, validate, and update license key.
- Tier badge and upgrade prompts are visible in admin UI.
- Features restricted/enabled according to tier (e.g., product limits, gateway access).
- For restricted features: clear tooltip or modal explaining upgrade options.
- Addons manager differentiates free/paid addons.

**UI Notes:**  
- License entry and tier info should be in sidebar or top bar (StoreEngine-style settings).
- Disabled/locked features should visually match StoreEngine “inactive” or “pro” features (see Addons panel).

---

## Next Steps

**Phase 4–6 (LMS, Payment, Product) will be in the next response.**  
Let me know if you want to add/remove any details or focus on specific areas!
