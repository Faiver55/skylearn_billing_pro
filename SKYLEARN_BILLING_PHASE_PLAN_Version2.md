# Skylearn Billing Pro – Detailed Phase Plan (continued)

---

## Phase 4: LMS Detection & Integration

### Tasks:
- [ ] Build LMS Manager class to:
  - Detect installed LMS plugins (LearnDash, TutorLMS, LifterLMS, etc.)
  - Allow admin to select one active LMS per site in settings
- [ ] Create course mapping UI:
  - Map products to courses or course bundles
  - Define enrollment triggers (payment, manual, webhook)
- [ ] Stub connectors for at least one LMS (e.g., LearnDash)
- [ ] Add extensibility for future multi-LMS/scenario support

### Acceptance Criteria:
- LMS Manager detects installed LMS and displays options in settings
- Admin can select an LMS; only mapped features are shown/enabled
- Course mapping UI allows product-to-course assignment (StoreEngine-style sidebar, tabs, searchable dropdowns)
- Enrollment logic works for selected LMS
- Extensible connector architecture for future LMS

**UI Notes:**  
- Use clean sidebar and tabbed navigation for LMS settings (like StoreEngine).
- Course mapping should use searchable/selectable fields and show mapping status.

---

## Phase 5: Payment Processor Integration

### Tasks:
- [ ] Build Payment Manager class:
  - Handle API credential setup for gateways
  - Display available gateways; enable/disable per tier/license
  - Add warning/notice logic for gateways with hosted checkout only (e.g., Lemon Squeezy)
- [ ] Integrate Stripe, Paddle, Lemon Squeezy, WooCommerce connectors
- [ ] Build customizable checkout field system:
  - Admin can add/edit/remove checkout fields per gateway
  - Conditional display logic (fields per product/gateway/tier)
- [ ] Inline, overlay, and hosted checkout templates:
  - Fallback to hosted checkout if required
  - Show warning to admin if hosted checkout required but not supported
- [ ] Handle payment notifications/webhooks

### Acceptance Criteria:
- Payment Manager lists all supported gateways; admin can configure credentials
- Tier logic restricts gateways for Free version
- Gateway warning/notice logic functions (e.g., Lemon Squeezy)
- Custom checkout fields are configurable and show/hide based on logic
- Checkout templates render correctly (feature-first; basic HTML wireframe)
- Webhook logic processes payment notifications

**UI Notes:**  
- Gateway settings mimic StoreEngine's clean sidebar/cards.
- Checkout field builder uses drag-and-drop or simple form UI.
- Warnings and notices styled as StoreEngine info modals.

---

## Phase 6: Product Management

### Tasks:
- [ ] Register custom post type for products (if needed)
- [ ] Build product CRUD (create, read, update, delete) UI in admin
- [ ] Implement product limits for Free tier (max 5 products)
- [ ] Product-to-course mapping interface (integrates with LMS mapping)
- [ ] Product status (active/inactive), visibility controls

### Acceptance Criteria:
- Products can be added, edited, deleted in admin
- Product limit enforced for Free tier, with upgrade prompt if exceeded
- Products can be mapped to courses (see Phase 4)
- Product status/visibility can be toggled
- Product list uses StoreEngine-style cards/table UI

**UI Notes:**  
- Product admin follows StoreEngine's product management layout (sidebar, tabbed details, clear action buttons).
- When limit is reached, display StoreEngine-style “upgrade” modal/notice.

---

## Next Steps

**Phase 7–9 (User Enrollment, Email, Portal) will be detailed in the next response!**
If you want to prioritize or change the order, let me know!