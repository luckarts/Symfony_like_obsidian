# ─── Stage 1 : Build ─────────────────────────────────────────────────────────
FROM node:22-alpine AS builder

RUN npm install -g pnpm@10

WORKDIR /app

COPY pnpm-lock.yaml pnpm-workspace.yaml package.json ./
RUN pnpm install --frozen-lockfile

COPY . .

ARG NUXT_PUBLIC_API_BASE
ENV NUXT_PUBLIC_API_BASE=$NUXT_PUBLIC_API_BASE

RUN pnpm build


# ─── Stage 2 : Runner ────────────────────────────────────────────────────────
FROM node:22-alpine AS runner

WORKDIR /app

# Nitro bundle les dépendances dans .output — rien d'autre à installer
COPY --from=builder /app/.output .output

ENV NODE_ENV=production
ENV NITRO_HOST=0.0.0.0
ENV NITRO_PORT=3000

EXPOSE 3000

CMD ["node", ".output/server/index.mjs"]
