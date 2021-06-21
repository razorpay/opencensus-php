import analyticsService from '@commander/services/analytics';

analyticsService.init({
  lumberjackAppName: 'pg-dashboard',
  lumberjackApiKey: window.LUMBERJACK_API_KEY,
  lumberjackApiUrl: window.LUMBERJACK_API_URL,
  segmentApiKey: window.SEGMENT_API_KEY,
});

export default analyticsService;
