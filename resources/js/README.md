# Vue structure

- `Pages/` holds Inertia route entry components.
- `Components/` holds shared UI, form, navigation, and branding components.
- `Features/` holds domain-specific Vue code for the food delivery flows.
- `Layouts/` holds page shells by app area.
- `Composables/` holds reusable client-side interaction logic.
- `Services/` holds browser integrations and API helper code.
- `Types/` holds shared type definitions.
- `Utils/` holds framework-agnostic helpers.

## Current conventions

- Put reusable field primitives under `Components/Forms/Fields/`.
- Use `TextField.vue` for standard label + input + error groups.
- Keep `Pages/` thin and move growing page-specific UI into `Features/`.
- Prefer domain names like `restaurants`, `cart`, and `checkout` over generic folders.
