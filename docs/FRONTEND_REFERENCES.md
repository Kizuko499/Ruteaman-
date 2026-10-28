# Frontend references implemented

Patterns were adapted to RuteAman’s palette, accessibility rules, and Blade/Alpine architecture. No third-party branding or demo content was copied.

## FreeFrontend

### Responsive mobile navigation

Source: https://freefrontend.com/css-mobile-menus

Implemented:

- Fixed bottom tab bar for mobile.
- Four primary destinations: Peta, Lapor, Laporanku, Akun.
- `env(safe-area-inset-bottom)` support.
- Transform-only selected-state movement.
- Visible keyboard focus and semantic `<nav>`.

### Skeleton loading

Source: https://freefrontend.com/css-skeleton-loadings/

Implemented:

- Route-card-shaped loading placeholders.
- One compositor-friendly pseudo-element sweeps across the container.
- Decorative blocks use `aria-hidden="true"` while the live region announces loading.
- Animation is disabled by `prefers-reduced-motion`.

### Card interaction feedback

Source: https://freefrontend.com/css-card-hover-effects

Implemented:

- `transform` and `box-shadow` feedback rather than layout-changing dimensions.
- Equivalent `:focus-visible`/`:focus-within` behavior.
- Route cards can be selected with Enter or Space.
- Motion disabled for reduced-motion users.

### Native accordion

Source: https://freefrontend.com/css-vertical-accordions

Implemented:

- Semantic `<details>` and `<summary>` FAQ.
- No JavaScript dependency.
- Clear open indicator and keyboard-native behavior.