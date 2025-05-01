FROM c.rzp.io/razorpay/rzp-docker-image-inventory-multi-arch:rzp-golden-image-base-node-22-alpine3.21 AS builder

ARG APP_NAME
ARG STAGE

ENV APP_NAME=$APP_NAME
ENV STAGE=$STAGE

RUN apk add --no-cache libc6-compat \
    && mkdir -p /dashboard

RUN npm install -g pnpm@~10.10.0 && \
 pnpm config set unsafe-perm true 

COPY pnpm-workspace.yaml .npmrc package.json /dashboard/
COPY plugins /dashboard/plugins
COPY libs/shared-core/package.json /dashboard/libs/shared-core/package.json
COPY libs/shared-types/package.json /dashboard/libs/shared-types/package.json
COPY libs/shared-utils/package.json /dashboard/libs/shared-utils/package.json
COPY libs/shared-qsuite/package.json /dashboard/libs/shared-qsuite/package.json
COPY libs/shared-ui/package.json /dashboard/libs/shared-ui/package.json
COPY apps/${APP_NAME}/package.json /dashboard/apps/${APP_NAME}/package.json

WORKDIR /dashboard

RUN --mount=type=secret,id=GITHUB_ACCESS_TOKEN \
 export GITHUB_ACCESS_TOKEN="$(cat /run/secrets/GITHUB_ACCESS_TOKEN)" && \
 pnpm install --ignore-scripts

RUN rm -f ./.npmrc

COPY nx.json /dashboard/
COPY libs /dashboard/libs
COPY apps/${APP_NAME} /dashboard/apps/${APP_NAME}

WORKDIR /dashboard/apps/$APP_NAME

ARG VERSION
ARG DASHBOARD_MICROAPPS
ENV VERSION=$VERSION \
    DASHBOARD_MICROAPPS=$DASHBOARD_MICROAPPS

RUN --mount=type=secret,id=SENTRY_AUTH_TOKEN \
 export SENTRY_AUTH_TOKEN="$(cat /run/secrets/SENTRY_AUTH_TOKEN)" && \
 TARGET_BUILD=server pnpm nx $STAGE:build

##############################################################################################################################

FROM c.rzp.io/razorpay/rzp-docker-image-inventory-multi-arch:rzp-golden-image-base-node-22-alpine3.21 AS runtime

ARG APP_NAME
ARG STAGE
ARG VERSION
ARG DASHBOARD_MICROAPPS

ENV APP_NAME=$APP_NAME \
    STAGE=$STAGE \
    VERSION=$VERSION \
    DASHBOARD_MICROAPPS=$DASHBOARD_MICROAPPS

EXPOSE 8888

RUN apk add --no-cache ca-certificates libc6-compat \
    && adduser -D -u 1001 ezio \
    && mkdir -p /dashboard \
    && chown -R ezio:ezio /dashboard
    
WORKDIR /dashboard

RUN npm install --production \
    dotenv@16.4.5 \
    @sentry/profiling-node@8.36.0 \
    && npm cache clean --force

COPY --from=builder /dashboard/apps/$APP_NAME/build/server /dashboard
COPY --from=builder /dashboard/apps/$APP_NAME/environment/.env.$STAGE /dashboard/.env.$STAGE

USER ezio

CMD node -r dotenv/config main.js dotenv_config_path=.env.$STAGE