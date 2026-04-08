## Summary

- Ajout du domaine User (entité, repository, password hasher)
- Authentification OAuth2 (password grant) via League OAuth2 Server
- Endpoints API Platform : register, login, get profile, update profile
- Tests E2E complets (register, login, get/update profile)

## Test plan

- [x] `make test-e2e` — 6 tests passent
- [x] Register → Login → Get Profile → Update Profile (happy path)
- [x] Email déjà existant → 409
- [x] Token invalide → 401