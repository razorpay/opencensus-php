# Dashboard E2E Success Rate Reporting

## What It Does

This script monitors Dashboard E2E test health across modules and automatically reports results via Slack. It fetches test metrics from Victoria Metrics, processes the data, and presents it in an easy-to-understand format with visual indicators.

The system supports both **automated daily reporting** (last 24 hours) and **custom time range analysis** for specific periods.

## Key Features

### 🎯 **Success Rate Monitoring**

• Color-coded status system for quick health assessment

- 🟢 Green: >90% success rate (healthy)
- 🟠 Amber: 50-90% success rate (needs attention)
- 🔴 Red: <50% success rate (critical)

### 📊 **Comprehensive Metrics Collection**

• Success rate percentages per module
• Total test runs and failed runs count
• Unique failure types tracking
• Module-specific detailed breakdowns

### 🕐 **Flexible Time Range Support**

• **Default Mode**: Automatically analyzes last 24 hours from current time
• **Custom Mode**: Supports specific time ranges via environment variables
• **Dynamic Duration Calculation**: Automatically calculates query duration based on time range
• **Grafana Integration**: Generates dashboard links with matching timestamps

### 🔄 **Automated Data Processing**

• Fetches data from Victoria Metrics API
• Gets multiple layers of metrics for better detailed understanding
• Two-level query approach for complete analysis:

- **Overall Micro Frontend Query**: Gets success rate for each micro frontend
- **Web Micro Frontend Detailed Query**: Since web micro frontend is very big, breaks it down further
  • Both query types track the same metrics:
- Overall Success Rate
- Total test runs
- Failed test runs
- Unique failures

### 💬 **Slack Integration**

• Automated notifications to team channels
• Dual-channel support (production vs test mode)

- Production: `#payments-dashboard`
- Test: `#testing-automation`
  • Formatted tables with emoji indicators
  • Automatic mention removal in test mode

### 🖥️ **Console Output**

• Formatted table display with aligned columns
• Summary statistics and breakdowns
• Color-coded visual indicators
• Detailed module information

## Main Functions

### **Data Collection**

• `queryVictoriaMetrics()` - Orchestrates comprehensive data gathering
• `queryVictoriaMetricsSingle()` - Handles individual API calls

### **Data Processing**

• `parseResponseToTable()` - Converts API responses to structured format

### **Output Generation**

• `displayTable()` - Creates formatted console output
• `formatTableForSlack()` - Prepares Slack-compatible messages
• `sendSlackMessage()` - Delivers notifications to channels

### **Utilities**

• `getColorCode()` - Determines status indicators based on success rates
• `getTimeRangeConfig()` - Generates time range configuration (default or custom)
• `formatTimestamp()` - Formats Unix timestamps for display in IST
• `getTimeRangeDisplay()` - Creates formatted time range strings

## Configuration

### **Environment Variables**

• `DASHBOARD_E2E_SLACK_BOT_TOKEN` - Required for Slack notifications
• `E2E_SLACK_TEST_MODE` - Set to "true" for test mode (uses test channel)
• `E2E_CUSTOM_FROM` - Optional: Custom start time (Unix timestamp in seconds)
• `E2E_CUSTOM_TO` - Optional: Custom end time (Unix timestamp in seconds)

### **Custom Timestamp Format**

When using custom time ranges, provide Unix timestamps in **seconds** (not milliseconds):

```yaml
env:
  E2E_CUSTOM_FROM: '1751352960' # July 1, 2025 12:26 PM IST
  E2E_CUSTOM_TO: '1751439360' # July 2, 2025 12:26 PM IST
```

**Example conversion:**

- **Grafana format:** `from=1751352960000&to=1751439360000` (milliseconds)
- **Required format:** `E2E_CUSTOM_FROM: "1751352960"` (seconds)

### **Workflow Configuration**

The GitHub Actions workflow (`.github/workflows/e2e-success-rate.yml`) supports:

• **Daily automated execution** at 12:00 AM IST
• **Manual triggering** via `workflow_dispatch`
• **Custom time ranges** by uncommenting and setting environment variables

### **Data Source**

• Victoria Metrics API endpoint for test execution data
• Prometheus-compatible query interface
