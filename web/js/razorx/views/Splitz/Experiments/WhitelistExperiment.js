import React from 'react';
import PropTypes from 'prop-types';
import { withRouter } from 'common/deprecated/withRouter';
import { ModalContent } from 'common/new-ui/Modal';
import { closeModal, notifySuccess, notifyError } from 'razorx/components/Modal';
import Form from 'razorx/components/ui/Form';
import { TextAreaField, SelectField, SearchableSelectField } from 'razorx/components/ui/Field';
import { splitzFetch } from 'razorx/helpers/fetch';

const EntityIds = 'Entity IDs';
const SegmentIds = 'Segment';

const getDropDownProperties = (experimentType) => {
  const options = [EntityIds];
  if (experimentType !== 'control_switch') {
    options.push(SegmentIds);
  }
  return options.map((item) => (
    <option key={item} value={item}>
      {item}
    </option>
  ));
};
class WhitelistExperiment extends React.Component {
  state = {
    isSaving: false,
    variants: this.props.variants,
    selectedWhitelistTypes: {},
    segments: null,
    selectedSegments: {},
  };

  segmentListFetch = () => {
    splitzFetch({
      url: 'segment.v1.SegmentAPI/List',
      data: {
        limit: 1000,
        offset: 0,
      },
    })
      .then((response) => {
        this.setState({ segments: response?.items });
      })
      .catch((err) => {
        notifyError(err);
      });
  };

  setSegments = () => {
    const { variants } = this.state;
    const currentSegments = {};
    variants.forEach((variant) => {
      const whitelistedSegmentIds = this.getWhitelistedSegmentIds(variant.id);
      if (whitelistedSegmentIds) {
        currentSegments[variant.id] = { id: whitelistedSegmentIds };
      }
    });
    this.setState({
      selectedSegments: currentSegments,
    });
  };

  componentDidMount() {
    this.segmentListFetch();
    this.setSegments();
  }

  onSubmit = () => {
    const { data } = this.props;
    const { variants, selectedSegments } = this.state;
    const payload = {
      id: data.id,
      whitelisting: variants.map((variant) => {
        const selectedSegment = selectedSegments[variant.id];
        if (selectedSegment) {
          return {
            entity_id: variant?.id,
            segment_id: selectedSegment?.id,
          };
        } else {
          return {
            entity_id: variant?.id,
            ids: variant?.whitelistedIds,
          };
        }
      }),
    };

    const url = 'experiment.v1.ExperimentAPI/Action';
    const successMsg = 'Successfully updated whitelist';

    this.setState({ isSaving: true });

    splitzFetch({ url, data: payload })
      .then(() => {
        this.setState({ isSaving: false });
        notifySuccess(successMsg);
        closeModal();
        this.props.onEdit();
      })
      .catch((err) => {
        this.setState({ isSaving: false });
        notifyError(err);
      });
  };

  getWhitelistedSegmentIds = (variantId) => {
    const { data } = this.props;

    if (!data.whitelisting || !data.whitelisting.length) {
      return null;
    }

    const whitelist = data.whitelisting.find((matchingId) => matchingId.entity_id === variantId);

    if (whitelist) {
      return whitelist.segment_id;
    }

    return null;
  };

  handleSelectedType = (e, variantId) => {
    const { selectedWhitelistTypes } = this.state;
    this.setState({
      selectedWhitelistTypes: {
        ...selectedWhitelistTypes,
        [variantId]: e.target.value,
      },
    });
  };

  handleSelectedSegment = (e, variantId) => {
    const { selectedSegments } = this.state;
    this.setState({
      selectedSegments: { ...selectedSegments, [variantId]: e.option },
    });
  };

  setWhitelistIds = ({ target: { value: ids } }, variantId, variants) => {
    const { selectedSegments } = this.state;
    this.setState({
      variants: variants.map((v) => {
        if (v.id === variantId) {
          return {
            ...v,
            whitelistedIds: ids.split(','),
          };
        }
        return v;
      }),
    });
    this.setState({
      selectedSegments: { ...selectedSegments, [variantId]: undefined },
    });
  };

  render() {
    const { isSaving, variants, selectedWhitelistTypes, segments, selectedSegments } = this.state;

    return (
      <ModalContent class="modal-features modal-json-edit" header="Whitelist Entity IDs">
        <Form
          onSubmit={this.onSubmit}
          class="full-span full-elements"
          style={{ opacity: isSaving ? 0.5 : 1 }}
        >
          {isSaving && <div className="spinner center" />}
          <div className="sub-description whitelist-description">
            <i className="fa fa-exclamation-circle whitelist-description-text" />
            Whitelisting with Entity IDs has limit of 50. To set higher limit, use
            &lsquo;Segment&rsquo; Type
          </div>
          <br />

          {variants.map((variant, index) => {
            const whitelistedSegmentIds = this.getWhitelistedSegmentIds(variant.id);
            return (
              <div className="segment pad-highlight whitelist-box" key={variant.id}>
                <span className="label variant-heading">Variant #{index + 1}</span>
                <div>
                  <span className="square-pills label-semi-muted variant-name">{variant.name}</span>
                </div>
                <SelectField
                  name="type"
                  label="Type"
                  defaultValue={whitelistedSegmentIds ? SegmentIds : EntityIds}
                  onChange={(e) => {
                    this.handleSelectedType(e, variant.id);
                  }}
                >
                  {getDropDownProperties(this.props?.data?.type)}
                </SelectField>
                <br />
                {selectedWhitelistTypes[variant.id] === EntityIds ||
                (!selectedWhitelistTypes[variant.id] && !whitelistedSegmentIds) ? (
                  <TextAreaField
                    label="Entity IDs: (max 50) (comma seperated)"
                    placeholder="Add comma separated list of IDs"
                    value={variant?.whitelistedIds?.join()}
                    onChange={({ target: { value: ids } }) => {
                      this.setWhitelistIds({ target: { value: ids } }, variant.id, variants);
                    }}
                  />
                ) : (
                  <SearchableSelectField
                    name="segment_id"
                    placeholder="Select a Segment ID"
                    label="Select Segment ID"
                    options={segments || []}
                    selected={
                      selectedSegments[variant.id]?.name ||
                      segments?.find(
                        (matchingId) =>
                          matchingId.id ===
                          (selectedSegments[variant.id]?.id || whitelistedSegmentIds),
                      )?.name
                    }
                    onChange={(e) => {
                      this.handleSelectedSegment(e, variant.id);
                    }}
                  />
                )}
              </div>
            );
          })}
          <div className="footer">
            <button className="btn btn--primary">
              Save Whitelisted IDs
              <span className="spin-btn" />
            </button>
          </div>
        </Form>
      </ModalContent>
    );
  }
}

WhitelistExperiment.defaultProps = {
  data: {},
  onEdit: () => {},
};

WhitelistExperiment.propTypes = {
  data: PropTypes.object,
  variants: PropTypes.array,
  onEdit: PropTypes.func,
};

export default withRouter(WhitelistExperiment);
