FROM node:20-bookworm-slim

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci --omit=dev

COPY . .

# Persist Baileys session across restarts (mount volume at /app/auth_session)
RUN mkdir -p /app/auth_session /app/auth_session_backup

ENV NODE_ENV=production
ENV PORT=3000

EXPOSE 3000

CMD ["node", "index.js"]
