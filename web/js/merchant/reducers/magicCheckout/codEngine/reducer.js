import { merge, remove, updateItem, push } from 'common/utils/immutable';
import { ACTIONS } from 'merchant/reducers/magicCheckout/codEngine/action';
import { COD_ENGINES, COD_ENGINE_TYPES } from 'merchant/views/MagicCheckout/CODSettings/constants';
import { formatResponse, buildZonesData } from 'merchant/reducers/magicCheckout/codEngine/utils';

const initialState = {
  loading: false, // TODO: add loading at zones, slabs & product level
  editMode: false,
  error: {},
  configs: {
    cod_engine: false,
    engine: COD_ENGINES.BASIC,
    cod_engine_type: COD_ENGINE_TYPES.SLAB_ELIGIBILITY,
  },
  fee_rules: [],
  zones: [],
  validations: {
    fee_rules: true,
    zones: true,
  },
};

export const magicCODSettingsReducer = (state = initialState, action) => {
  switch (action.type) {
    case ACTIONS.FETCH_CONFIG_SUCCESS: {
      const { data } = action.payload;
      const { configs, fee_rules = [], zones = [], editMode } = formatResponse(data);
      return merge(state, {
        loading: false,
        editMode,
        configs,
        zones: zones ?? [],
        fee_rules: fee_rules ?? [],
        validations: {
          fee_rules: true,
          zones: true,
        },
      });
    }
    case ACTIONS.UPDATE_ENGINE_CONFIG:
      return merge(state, {
        ...state,
        configs: {
          ...state.configs,
          ...action.payload,
        },
      });
    // FEE RULES
    case ACTIONS.UPSERT_FEE_RULES_SUCCESS:
      return merge(state, {
        ...state,
        fee_rules: action.payload.data.fee_rules,
        loading: false,
        validations: { ...state.validations, fee_rules: true },
      });
    case ACTIONS.UPDATE_FEE_RULE_SUCCESS: {
      const feeIdx = state.fee_rules.findIndex((f) => f.id === action.payload.data.id);
      const editedFeeRules = updateItem(state.fee_rules, feeIdx, action.payload.data);
      return merge(state, {
        ...state,
        fee_rules: editedFeeRules,
        loading: false,
      });
    }
    case ACTIONS.DELETE_FEE_RULE_SUCCESS: {
      const newFeeRules = remove(state.fee_rules, (item) => item.id === action.rule_id);
      return merge(state, {
        ...state,
        fee_rules: newFeeRules,
        loading: false,
      });
    }

    // ZONES
    case ACTIONS.CREATE_ZONE_SUCCESS: {
      const addedZones = push(state.zones, ...buildZonesData([action.payload.data]));
      return merge(state, {
        ...state,
        zones: addedZones,
        loading: false,
        validations: { ...state.validations, zones: true },
      });
    }
    case ACTIONS.UPDATE_ZONE_SUCCESS: {
      const idx = state.zones.findIndex((z) => z.id === action.payload.data.id);
      // eslint-disable-next-line no-case-declarations
      const editedZones = updateItem(state.zones, idx, ...buildZonesData([action.payload.data]));
      return merge(state, {
        ...state,
        zones: editedZones,
        loading: false,
      });
    }
    case ACTIONS.DELETE_ZONE_SUCCESS: {
      const newZones = remove(state.zones, (item) => item.id === action.zone_id);
      return merge(state, {
        ...state,
        zones: newZones,
        loading: false,
      });
    }

    case ACTIONS.VALIDATE_CONFIG:
      return merge(state, {
        ...state,
        validations: {
          ...state.validations,
          [action.payload.key]: action.payload.valid,
        },
      });

    case ACTIONS.SET_EDIT_MODE:
      return merge(state, {
        ...state,
        editMode: action.value,
      });

    case ACTIONS.FETCH_CONFIG_PENDING:
    case ACTIONS.UPSERT_FEE_RULES_PENDING:
    case ACTIONS.UPDATE_FEE_RULE_PENDING:
    case ACTIONS.DELETE_FEE_RULE_PENDING:
    case ACTIONS.CREATE_ZONE_PENDING:
    case ACTIONS.UPDATE_ZONE_PENDING:
    case ACTIONS.DELETE_ZONE_PENDING:
      return merge(state, { loading: true });
    case ACTIONS.FETCH_CONFIG_ERROR:
    case ACTIONS.UPSERT_FEE_RULES_ERROR:
    case ACTIONS.UPDATE_FEE_RULE_ERROR:
    case ACTIONS.DELETE_FEE_RULE_ERROR:
    case ACTIONS.CREATE_ZONE_ERROR:
    case ACTIONS.UPDATE_ZONE_ERROR:
    case ACTIONS.DELETE_ZONE_ERROR:
      return merge(state, { loading: false, error: action.payload?.error });
    default:
      return state;
  }
};
