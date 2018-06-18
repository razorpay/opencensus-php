import PropTypes from 'prop-types';
import { Label, inputClass } from './index';
import { classList } from 'common/util';
import Button, { AsyncBtn } from 'component/Button';
import ErrorBoundary from 'common/ErrorBoundary';

/* Pair is key-value pair*/
export default class EditablePairsList extends React.PureComponent {
  className = 'Input--pair Input-pair--editable';

  static contextTypes = {
    confirm: PropTypes.func,
  };

  state = this.initializeState();

  initializeState() {
    const initialPairs = this.props.defaultValue || [];

    let dummyTS = new Date().getTime();

    const initialKeys = initialPairs.map(p => {
      return dummyTS++;
    });

    return {
      maxAllowedPairs: this.props.maxAllowedPairs || 10,
      pairs: initialPairs,
      keys: initialKeys,
    };
  }

  onAddNew = e => {
    this.props.trackerFn('Add New Notes');

    const freshPairs = [...this.state.pairs];
    const freshKeys = [...this.state.keys];

    freshPairs.push({});

    const dummyTS = freshKeys[freshKeys.length - 1] || new Date().getTime();
    freshKeys.push(dummyTS + 1);

    this.setState({
      pairs: freshPairs,
      keys: freshKeys,
    });

    setTimeout(
      () =>
        document
          .getElementsByName(
            `${this.props.name}[${freshPairs.length - 1}][key]`
          )[0]
          .focus(),
      10
    );
  };

  removePair = pairIdx => {
    let freshPairs = [...this.state.pairs];
    let freshKeys = [...this.state.keys];

    freshPairs.splice(pairIdx, 1);
    freshKeys.splice(pairIdx, 1);

    this.setState({
      pairs: freshPairs,
      keys: freshKeys,
    });

    return freshPairs;
  };

  /* Handle click on Delete */
  deletePair = pairIdx => {
    this.props.trackerFn('Delete Notes');

    this.context.confirm({
      header: 'Delete Note?',
      message: 'Are you sure you want to delete this note?',
      affirmativeLabel: 'Delete',
      affirmativePendingLabel: 'Deleting',
      action: () => {
        const freshPairs = this.removePair(pairIdx);
        this.props.trackerFn('Delete Notes Confirmed');

        return this.props.saveAndUpdate(freshPairs);
      },
    });
  };

  handleSave = (pairIdx, pair) => {
    // Expects parent is saving pairs in same form as this.state.pairs, unlike in 'isBunchSave = true' mode.
    const freshPairs = [...this.state.pairs];
    freshPairs[pairIdx] = pair;

    this.props.trackerFn('Save Notes');

    return this.props.saveAndUpdate(freshPairs).then(resp => {
      if (resp.data) {
        this.setState({
          pairs: freshPairs,
        });
      }

      return resp;
    }); // Sending only the edited pair. To save in Parent's state.notes and make api call
  };

  render() {
    return (
      <ErrorBoundary>
        <div
          class={classList(
            inputClass(this),
            !!this.state.pairs.length && 'isExpanded'
          )}
        >
          <Label text={this.props.label} />
          <div class="Input-content">
            {!!this.state.pairs.length &&
              this.state.pairs.map((pair, idx) => (
                <PairDecider
                  key={this.state.keys[idx]}
                  name={this.props.name}
                  idx={idx}
                  defaultValue={this.state.pairs[idx]}
                  deletePair={this.deletePair}
                  handleSave={this.handleSave}
                  removePair={this.removePair}
                  trackerFn={this.props.trackerFn}
                />
              ))}

            {this.state.pairs.length < this.state.maxAllowedPairs ? (
              <Button.Transparent
                type="button"
                class="Btn--Link"
                onClick={this.onAddNew}
              >
                + Add New
              </Button.Transparent>
            ) : null}
          </div>
        </div>
      </ErrorBoundary>
    );
  }
}

/*
 * Pair can be of several types:
 * Pair View
 * 1. Just title and description as view.
 * 2. Same as 1 but modifiable (Edit+Delete).
 *
 * InputEditable Pair
 * 3. Allow Input(title+descriptipon) inside Pair to be editable.
 * 4. Same as 3 but with it's own save button.
 *
 *
 * Note A: 3rd and 4th case comes when clicked on '+ Add New' or clicked on 'Edit' from 2nd view.
 * Note B: 2nd and 4th case will exist only when 'isBunchSave = true'.
 * Notes C: If parent is having bunch
 * */

/* Decides what type of Pair to be shown */
class PairDecider extends React.Component {
  // If Pair defaultValue, then show as PairView
  state = {
    isPairInputEditable: !(
      this.props.defaultValue['key'] || this.props.defaultValue['value']
    ),
  };

  toggleEditMode = e => {
    const isPairInputEditable = !this.state.isPairInputEditable;

    this.setState({
      isPairInputEditable,
    });
  };

  handleSave = pair => {
    const promise = this.props.handleSave(this.props.idx, pair);

    if (promise) {
      promise.then(resp => {
        if (resp.data) {
          this.toggleEditMode();
        }
      });
    }

    return promise;
  };

  render() {
    const { name, idx } = this.props;

    return this.state.isPairInputEditable ? (
      <InputEditablePair
        name={name}
        idx={idx}
        defaultValue={this.props.defaultValue}
        updateKey={this.updateKey}
        updateValue={this.updateValue}
        toggleEditMode={this.toggleEditMode}
        removePair={this.props.removePair}
        handleSave={this.handleSave}
      />
    ) : (
      <PairView
        idx={idx}
        pair={this.props.defaultValue}
        deletePair={this.props.deletePair}
        makeEditable={this.toggleEditMode}
        trackerFn={this.props.trackerFn}
      />
    );
  }
}

class InputEditablePair extends React.Component {
  state = {
    pair: this.props.defaultValue,
  };

  onFocusTitle = e => {
    this.setState({
      focusTitle: true,
    });
  };

  onBlurTitle = e => {
    this.setState({
      focusTitle: false,
    });
  };

  onFocusDesc = e => {
    this.setState({
      focusDesc: true,
    });
  };

  onBlurDesc = e => {
    this.setState({
      focusDesc: false,
    });
  };

  handleCancelClick = e => {
    const defaultValue = this.props.defaultValue;

    if (defaultValue.key || defaultValue.value) {
      this.setState({
        pair: defaultValue, // Setting to initial default value
      });

      this.props.toggleEditMode();
    } else {
      this.props.removePair(this.props.idx);
      // No need to toggle in this case, because it won't be rendered in next cycle as it's getting removed
    }
  };

  updatePair = (e, field) => {
    const freshPair = { ...this.state.pair };

    freshPair[field] = e.target.value;

    this.setState({
      pair: freshPair,
    });
  };

  updateKey = e => {
    this.updatePair(e, 'key');
  };

  updateValue = e => {
    this.updatePair(e, 'value');
  };

  render() {
    const { name, idx, handleSave } = this.props;

    return (
      <div class="pair--editable">
        <div
          class={classList(
            'Input-pair',
            (this.state.focusDesc || this.state.focusTitle) && 'is-focused'
          )}
        >
          <div class="Input-elWrapper">
            <input
              class="Input-el Input-el--after"
              name={`${name}[${idx}][key]`}
              placeholder="Title (key)"
              data-id={idx}
              onChange={this.updateKey}
              value={this.state.pair.key || ''}
              onBlur={this.onBlurTitle}
              onFocus={this.onFocusTitle}
            />
          </div>

          <div class="Input-pair-separator" />
          <div class="Input-elWrapper">
            <textarea
              class="Input-el"
              name={`${name}[${idx}][value]`}
              placeholder="Description (value)"
              data-id={idx}
              onChange={this.updateValue}
              value={this.state.pair.value || ''}
              onBlur={this.onBlurDesc}
              onFocus={this.onFocusDesc}
            />
          </div>
        </div>
        <div style={{ textAlign: 'right', marginBottom: 12 }}>
          <Button.Transparent
            class="Button--Link"
            onClick={this.handleCancelClick}
          >
            Cancel
          </Button.Transparent>

          <AsyncBtn.Primary
            class="Button--small"
            disabled={!this.state.pair['key'] && !this.state.pair['value']}
            data-id={idx}
            style={{ marginRight: 0, marginLeft: 16 }}
            onClick={() => handleSave(this.state.pair)}
            pendingState="Saving"
          >
            Save
          </AsyncBtn.Primary>
        </div>
      </div>
    );
  }
}

class PairView extends React.Component {
  state = {};

  render() {
    const { name, idx, pair, makeEditable, deletePair } = this.props;

    return (
      <div class="pair--view">
        <div class="title">{pair.key}</div>
        <div class="description">{pair.value}</div>

        {makeEditable && (
          <Button.Transparent
            type="button"
            class="Btn--Link"
            onClick={() => {
              makeEditable();
              this.props.trackerFn('Edit Notes');
            }}
          >
            Edit
          </Button.Transparent>
        )}
        {deletePair && (
          <Button.Transparent
            type="button"
            class="Btn--Link Button--danger"
            onClick={() => deletePair(idx)}
          >
            Delete
          </Button.Transparent>
        )}
      </div>
    );
  }
}
