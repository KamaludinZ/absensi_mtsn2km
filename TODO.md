# TODO Fix E2E Regressions

- [x] Add missing security logs page view (`resources/views/admin/security/logs.blade.php`) to prevent `/admin/security/logs` 500 error.
- [x] Add editable security settings form on `/admin/security` (security dashboard) and backend handler route/action.
- [x] Fix employee username update persistence by allowing username edits in employee edit form and ensuring safe validation.
- [x] Fix student NISN persistence/display by adding `nisn` to Student model fillable.
- [x] Restore employee status filter UI + backend filtering on employee index page.
- [x] Fix holiday filter rehydration so selected year/month match request params.
- [ ] Run focused checks/tests (route:list, php lint, and targeted feature checks if available).
