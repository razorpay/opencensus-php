const cdnDashboardUrl = window.cdnDashboardUrl || '';
const publicPath = cdnDashboardUrl === 'https://localhost:8080' ? '/public' : '';

__webpack_public_path__ = `${cdnDashboardUrl}${publicPath}/dist/`;
