import initStoryshots, { Stories2SnapsConverter } from '@storybook/addon-storyshots';
import { render } from 'test-utils';
import '@testing-library/jest-dom/extend-expect';
Element.prototype.scrollIntoView = jest.fn();

const reactTestingLibrarySerializer = {
  print: (val, serialize) => serialize(val.container.firstChild),
  test: (val) => val && val.hasOwnProperty('container'),
};

initStoryshots({
  integrityOptions: { cwd: __dirname },
  enderer: render,
  snapshotSerializers: [reactTestingLibrarySerializer],
  asyncJest: true, // This is the option that activates the async behaviour
  test: ({
    story,
    context,
    done, // --> callback passed to test method when asyncJest option is true
  }) => {
    const converter = new Stories2SnapsConverter();
    const snapshotFilename = converter.getSnapshotFileName(context);
    const storyElement = story.render();

    // mount the story
    const { container } = render(storyElement);

    // wait until the mount is updated, in our app mostly by Relay
    // but maybe something else updating the state of the component
    // somewhere
    const waitTime = 1;
    setTimeout(() => {
      if (snapshotFilename) {
        expect(container).toMatchSpecificSnapshot(snapshotFilename);
      }
      done();
    }, waitTime);
  },
});
