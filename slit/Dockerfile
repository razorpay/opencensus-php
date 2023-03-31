# Stage - Build & Test
FROM --platform=linux/amd64 golang:1.17
ARG GIT_USERNAME
ENV CGO_ENABLED 0

WORKDIR /go/src/github.com/razorpay/api/slit

RUN apt-get install git

# Avoid copying the whole source code first since that will invalidate
# cache for all further layers
COPY go.mod .
COPY go.sum .

RUN --mount=type=secret,id=git_token set -eux && \
    echo "machine github.com login ${GIT_USERNAME} password $(cat /run/secrets/git_token)" > ~/.netrc && \
    export GOPRIVATE="github.com/razorpay/*" && \
    export GO111MODULE="on" &&\
    export DOCKER_BUILDKIT=0 && \
    export COMPOSE_DOCKER_CLI_BUILD=0 && \
    go mod download && \
    rm -f ~/.netrc

# Copy rest of the source code
ADD . .

RUN cd /go/pkg/mod/github.com/razorpay/goutils/itf@v0.2.0/cmd/reporter && go install

# install sonar plugin
# install helper commands
RUN apt-get update && apt-get install zip -y
RUN apt-get install wget -y
#download sonarscanner

ENV SONAR_RUNNER_HOME=sonar-scanner
ENV PATH $PATH:sonar-scanner/bin

RUN wget -O sonarscanner.zip https://binaries.sonarsource.com/Distribution/sonar-scanner-cli/sonar-scanner-cli-3.3.0.1492-linux.zip   \
  && unzip -qq sonarscanner.zip \
  && rm -rf sonarscanner.zip \
  && mv sonar-scanner-3.3.0.1492-linux sonar-scanner

ENTRYPOINT set -eux pipefail && \
    API_BASEPATH=/src go test -timeout=60m -v -json ./ -args -parallel | \
    reporter -host $REPORT_PORTAL_HOST  \
    -project $REPORT_PORTAL_PROJECT  \
    -token $REPORT_PORTAL_TOKEN \
    -name "api-slit" \
    -build_id $COMMIT_ID  \
    -coverage_base_path=/go/src/github.com/razorpay/api/slit/coverage \
    -coverage_enabled "true"\
    -skip_on_failure "true"
