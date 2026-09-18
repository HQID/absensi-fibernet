# Dashboard Redesign Implementation Plan

This document outlines the plan to redesign the admin dashboard to match the layout and soft/dark visual style of the provided reference image, while retaining the existing Fibernet color theme (but in a softer, muted palette) and keeping the existing database data structure.

## User Review Required

> [!IMPORTANT]
> The reference image features a horizontal date selector and "Sort By / Filters" buttons. Since this plan focuses on the UI/UX redesign, these new elements will be added visually (as UI components). Making them functional (actually filtering the database) would require additional Livewire/backend logic. **Please confirm if you want these to be purely visual for now, or if you also want me to implement the backend filtering logic for them.**

## Open Questions

> [!NOTE]
> 1. In the reference image, the check-in/out column shows the total duration between points (e.g., `--- 8h 58m ---`). Do you want me to calculate this duration dynamically based on `check_in_at` and `check_out_at`, or just draw the line without the duration text?
> 2. The reference image has a "Type" column (In house / Remote). Since your data has "Jarak" (Distance), I plan to display the distance there. Is this acceptable?

## Proposed Changes

---

### Global Styles & Sidebar (`resources/css/app.css` & Sidebar Component)

The sidebar will be updated to a solid, softer dark tone without the bright gradients, and the active state will use a soft cyan highlight to match the reference image's layout but with Fibernet's colors.

#### [MODIFY] `resources/css/app.css`
- Update `.fibernet-sidebar` to use a softer dark color (e.g., slate-800 or dark teal/charcoal) instead of the bright blue-to-black gradient.
- Update sidebar active state (`[data-current]`) to a soft cyan/blue background with a left border, removing the bright gradients.
- Add utility classes for the new soft stat cards (e.g., `.card-soft-cyan`, `.card-soft-rose`).
- Remove `.fibernet-page-header` gradient as the new header will be clean and minimal.

#### [MODIFY] `resources/views/layouts/app/sidebar.blade.php`
- Adjust text colors to be softer (e.g., slate-400 for inactive, white for active).

---

### Admin Dashboard (`resources/views/dashboard/admin.blade.php`)

The entire layout will be refactored to match the reference image.

#### [MODIFY] `resources/views/dashboard/admin.blade.php`
- **Page Header**: Remove the blue gradient banner. Replace it with a clean, text-only header ("Attendance") with a search bar and profile icons on the right.
- **Stat Cards**: 
  - Restructure to exactly 4 cards.
  - Card 1 (Total Peserta): Soft cyan background (`bg-cyan-50`), dark text.
  - Card 2 (Tidak Hadir): Soft rose/red background (`bg-rose-50`), dark text.
  - Card 3 (Hadir Tepat Waktu): White background, soft green text/icons.
  - Card 4 (Terlambat): White background, soft amber text/icons.
- **Date Selector & Filters**: Add a new horizontal bar with dates (Saturday to Friday) and the active date highlighted, along with "Sort By" and "Filters" buttons.
- **Data Table**:
  - Make the header transparent/clean.
  - **Employee Name**: Keep avatar + name + student code, but style text softer.
  - **Check-In & Out**: Combine into a single column with a visual timeline `[Time] o--- [Duration] ---o [Time]`. If no check-out, it will show an incomplete line.
  - **Designation**: Display "Sekolah" (School) here in soft gray text.
  - **Type**: Display "Jarak" (Distance) here.
  - **Status**: Update badges to be soft pastel colors (e.g., soft green background with green text for PRESENT, soft red for ABSENT) without borders.

---

## Verification Plan

### Manual Verification
1. I will load the dashboard page and verify that the layout matches the image (sidebar, header, 4 cards, date selector, table).
2. I will verify that the colors are softer/muted versions of the Fibernet theme.
3. I will ensure that all existing data (Total students, attendance counts, and table rows) are displayed correctly in the new layout.
4. I will check that the timeline visualization in the table renders properly for students with and without check-out times.
