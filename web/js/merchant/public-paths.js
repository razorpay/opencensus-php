const cdnDashboardUrl = window.cdnDashboardUrl || '';
const isLocalHost = cdnDashboardUrl === 'https://localhost:8080';
const publicPath = isLocalHost ? '/public' : '';
// window.selfServeUrl = 'https://localhost:9999';

__webpack_public_path__ = `${cdnDashboardUrl}${publicPath}/dist/`;
