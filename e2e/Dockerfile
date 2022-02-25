# Stage - Build & Test
FROM c.rzp.io/proxy_dockerhub/library/golang:1.16.3-alpine3.12

ARG GIT_TOKEN
ENV CGO_ENABLED 0

WORKDIR /go/src/github.com/razorpay/api/e2e

RUN apk add --update --no-cache --repository https://dl-4.alpinelinux.org/alpine/latest-stable/community/ make git

# Avoid copying the whole source code first since that will invalidate
# cache for all further layers
COPY go.mod .
COPY go.sum .

RUN set -eux && \
    echo 'exec echo ${GIT_TOKEN}' > /tmp/askpass.sh && \
    chmod +x /tmp/askpass.sh && \
    export GIT_ASKPASS=/tmp/askpass.sh && \
    go mod download

# Copy rest of the source code
ADD . .

RUN cd /go/pkg/mod/github.com/razorpay/goutils/itf@v0.0.8/cmd/reporter && go install

ENTRYPOINT set -euo pipefail && \
    go test -v -json ./... -args -parallel | \
    reporter -host $REPORT_PORTAL_HOST -project $REPORT_PORTAL_PROJECT -token $REPORT_PORTAL_TOKEN -name $REPORT_NAME -build_id $COMMIT_ID
