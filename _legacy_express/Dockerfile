# ==========================================================================
# SEMARAK ALIYAH XII 2026 - PRODUCTION DOCKERFILE
# ==========================================================================

FROM node:24-alpine AS runner

WORKDIR /app

# Set production environment
ENV NODE_ENV=production
ENV PORT=8080

# Install dependencies first (layer caching)
COPY package*.json ./
RUN npm install --omit=dev

# Copy application source
COPY . .

# Expose server port
EXPOSE 8080

# Run server
CMD ["node", "server.js"]
