const cdnDashboardUrl = window.cdnDashboardUrl || '';
const isLocalHost = cdnDashboardUrl === 'https://localhost:8080';
const publicPath = isLocalHost ? '/public' : '';

__webpack_public_path__ = `${cdnDashboardUrl}${publicPath}/dist/`;
