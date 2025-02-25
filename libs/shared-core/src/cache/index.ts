import { DASHBOARD_ROOT } from '../constants';
import { CacheManager } from './CacheManager';

const cacheManager = new CacheManager(DASHBOARD_ROOT);

export { cacheManager };
