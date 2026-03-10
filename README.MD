# Boilerplate authentification OAuth2 — Next.js + Symfony

**Backend** : Symfony 7.3 · DDD / Hexagonal · CQRS · OAuth2 · API Platform 4.1 · PostgreSQL  
**Frontend** : Next.js · shadcn/ui · TypeScript

## Objectif

Boilerplate Symfony avec authentification OAuth2 complète — base pour construire une app style Obsidian.

| Phase | Statut | Contenu |
|-------|--------|---------|
| Boilerplate | 🔄 | User, OAuth2, DDD/Hexagonal, API Platform |
| MVP | ⬜ | Notes, liens, vault, markdown |
| SaaS | ⬜ | Multi-tenant, partage, collaboration |

## Structure du dépôt

```
.
├── backend/      # API Symfony (DDD · CQRS · OAuth2)
│   └── README.md
├── frontend/     # App Next.js (TypeScript · shadcn/ui)
└── .github/      # CI/CD (quality, tests, audit, deploy)
```

## Documentation

- [`backend/README.md`](backend/README.md) — Architecture, Quick Start, Endpoints, Tests
