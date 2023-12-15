import { delay } from 'common/utils/timeout';
import { delay as testDelay } from 'test-utils';

describe('timeout utils', () => {
  test('delay util', async () => {
    let delayEnded = false;
    delay(1000).then(() => {
      delayEnded = true;
    });
    expect(delayEnded).toBe(false);
    await testDelay(1000);
    expect(delayEnded).toBe(true);
  });
});
