import { STAGE } from '@apps/shell/src/env';
import winston, { format } from 'winston';
import { LogLevel, LogType } from './shellLogger';

const { timestamp, json, printf, combine } = format;

const levelColors = {
  error: '\x1b[31m', // Red
  warn: '\x1b[33m', // Yellow
  info: '\x1b[90m', // Bright Gray
  success: '\x1b[32m', // Green
  reset: '\x1b[0m', // Reset color
};

const customLevels = {
  levels: {
    error: 0,
    warn: 1,
    info: 2,
    success: 2,
  },
};

const customFormat = printf(
  ({
    level,
    message,
    context,
    moduleName,
    error,
  }: LogType & {
    error?: unknown;
    level: LogLevel;
  }) => {
    const targetModule = `${levelColors[level]}[${moduleName}]${levelColors.reset}`;
    const messageInfo = ` message: ${levelColors[level]}"${message}"${levelColors.reset}`;
    const errorInfo = error
      ? `  error: ${levelColors[level]}${JSON.stringify(error, null, 2)}${levelColors.reset},\n`
      : '';
    const contextInfo = context
      ? `  context: ${levelColors[level]}${JSON.stringify(context, null, 2)}${levelColors.reset}\n`
      : '';
    return `✨ ${targetModule}: {\n ${messageInfo},\n${errorInfo}${contextInfo}}`;
  },
);

// Production log configuration
const logConfigurationProd = {
  levels: customLevels.levels,
  format: combine(timestamp(), json()),
  transports: [new winston.transports.Console()],
};

// Development log configuration with custom formatting
const logConfigurationDev = {
  levels: customLevels.levels,
  format: combine(customFormat),
  transports: [new winston.transports.Console()],
};

const logConfig = STAGE != 'development' ? logConfigurationProd : logConfigurationDev;

export default logConfig;
