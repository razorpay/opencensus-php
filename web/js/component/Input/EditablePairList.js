import PropTypes from 'prop-types';
import { Label, inputClass } from './index';
import debounce from 'rzp/utils/debounce';
import { classList } from 'common/util';
import Button, { AsyncBtn } from 'component/Button';
import ErrorBoundary from 'common/ErrorBoundary';

/* Pair is key-value pair*/
export default class EditablePairsList extends React.PureComponent {
  className = 'Input--pair Input-pair--editable';

  static contextTypes = {
    confirm: PropTypes.func,
  };

  state = {
    maxAllowedPairs: this.props.maxAllowedPairs || 10,
    pairs: this.props.defaultValue || [],
  };

  onAddNew = e => {
    const freshPairs = [...this.state.pairs];
    freshPairs.push({
      key: '',
      value: '',
    });

    this.setState({
      pairs: freshPairs,
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

  removePair = pairId => {
    let freshPairs = [...this.state.pairs];
    freshPairs.splice(pairId, 1);

    this.setState({
      pairs: freshPairs,
    });

    return freshPairs;
  };

  /* Handle click on Delete */
  deletePair = pairId => {
    this.context.confirm({
      header: 'Delete Note?',
      message: 'Are you sure you want to delete this note?',
      affirmativeLabel: 'Delete',
      affirmativePendingLabel: 'Deleting',
      action: () => {
        const freshPairs = this.removePair(pairId);

        return this.props.saveAndUpdate(freshPairs);
      },
    });
  };

  handleSave = (pairId, pair) => {
    // Expects parent is saving pairs in same form as this.state.pairs, unlike in 'isBunchSave = true' mode.
    const freshPairs = [...this.state.pairs];
    freshPairs[pairId] = pair;

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
                  key={idx}
                  name={this.props.name}
                  idx={idx}
                  defaultValue={this.state.pairs[idx]}
                  deletePair={this.deletePair}
                  handleSave={this.handleSave}
                  removePair={this.removePair}
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
    pair: this.props.defaultValue,
    isPairInputEditable: !(
      this.props.defaultValue['key'] || this.props.defaultValue['value']
    ),
  };

  /*
   * Clicked on Cancel Pair
   * Set state.notes[idx] if props.notes[idx] exists, otherwise simple delete
   * */
  toggleEditMode = e => {
    const isPairInputEditable = !this.state.isPairInputEditable;

    this.setState({
      isPairInputEditable,
    });

    const pairId = this.props.idx;

    if (!isPairInputEditable) {
      const defaultValue = this.props.defaultValue;

      if (!defaultValue.keys || !defaultValue.value) {
        this.props.removePair(pairId);
      } else {
        this.setState({
          pair: { ...this.props.originalValue },
        });
      }
    }
  };

  deletePair = e => {
    const pairId = pairId;
    this.props.deletePair(pairId);
  };

  handleSave = e => {
    const pairId = this.props.idx;
    const promise = this.props.handleSave(pairId, this.state.pair);

    if (promise) {
      promise.then(resp => {
        if (resp.data) {
          this.setState({
            isPairInputEditable: false,
          });
        }
      });
    }

    return promise;
  };

  updatePair = (e, field) => {
    const pairId = e.currentTarget.dataset.id;

    if (pairId > -1) {
      const pair = { ...this.state.pair };

      pair[field] = e.target.value;

      this.setState({
        pair,
      });
    }
  };

  updateKey = e => {
    this.updatePair(e, 'key');
  };
  updateValue = e => {
    this.updatePair(e, 'value');
  };

  render() {
    const { name, idx } = this.props;

    return this.state.isPairInputEditable ? (
      <InputEditablePair
        name={name}
        idx={idx}
        pair={this.state.pair}
        updateKey={this.updateKey}
        updateValue={this.updateValue}
        cancelEditMode={this.toggleEditMode}
        handleSave={this.handleSave}
      />
    ) : (
      <PairView
        idx={idx}
        pair={this.state.pair}
        deletePair={this.deletePair}
        makeEditMode={this.toggleEditMode}
      />
    );
  }
}

class InputEditablePair extends React.Component {
  state = {};

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

  render() {
    const { name, idx, pair, updateKey, updateValue, handleSave } = this.props;

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
              onChange={updateKey}
              value={pair.key}
              onBlur={this.onBlurTitle}
              onFocus={this.onFocusTitle}
            />
            {!handleSave && (
              <span
                class="Input-addons Input-addons--after Input-addons--clickable"
                data-id={idx}
                onClick={this.props.removePair}
              >
                <i class="i i-close" />
              </span>
            )}
          </div>

          <div class="Input-pair-separator" />
          <div class="Input-elWrapper">
            <textarea
              class="Input-el"
              name={`${name}[${idx}][value]`}
              placeholder="Description (value)"
              data-id={idx}
              onChange={updateValue}
              value={pair.value}
              onBlur={this.onBlurDesc}
              onFocus={this.onFocusDesc}
            />
          </div>
        </div>
        <div style={{ textAlign: 'right', marginBottom: 12 }}>
          <Button.Transparent
            class="Button--Link"
            onClick={this.props.cancelEditMode}
          >
            Cancel
          </Button.Transparent>

          <AsyncBtn.Primary
            class="Button--small"
            disabled={!pair['key'] && !pair['value']}
            data-id={idx}
            style={{ marginRight: 0, marginLeft: 16 }}
            onClick={handleSave}
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
    const { name, idx, pair, makeEditMode, deletePair } = this.props;

    return (
      <div class="pair--view">
        <div class="title">{pair.key}</div>
        <div class="description">{pair.value}</div>

        {makeEditMode && (
          <Button.Transparent
            type="button"
            class="Btn--Link"
            onClick={makeEditMode}
          >
            Edit
          </Button.Transparent>
        )}
        {deletePair && (
          <Button.Transparent
            type="button"
            class="Btn--Link Button--danger"
            onClick={deletePair}
          >
            Delete
          </Button.Transparent>
        )}
      </div>
    );
  }
}
