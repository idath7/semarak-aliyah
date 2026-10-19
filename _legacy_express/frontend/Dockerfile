# ==========================================================================
# SEMARAK ALIYAH XII 2026 - FRONTEND EXPRESS DOCKERFILE
# ==========================================================================

FROM node:24-alpine

WORKDIR /app

ENV NODE_ENV=production
ENV PORT=8080

COPY package*.json ./
RUN npm install --omit=dev

COPY . .

EXPOSE 8080

CMD ["node", "server.js"]
