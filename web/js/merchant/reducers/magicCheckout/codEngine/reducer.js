import { merge, remove, updateItem, push } from 'common/utils/immutable';
import { ACTIONS } from 'merchant/reducers/magicCheckout/codEngine/action';
import { COD_ENGINES, COD_ENGINE_TYPES } from 'merchant/views/MagicCheckout/CODSettings/constants';
import { formatResponse, buildZonesData } from 'merchant/reducers/magicCheckout/codEngine/utils';

const initialState = {
  loading: {
    summary: false,
    fee_rules: false,
    zones: false,
    item_categories: false,
    mapping: false,
  },
  editMode: false,
  error: {},
  configs: {
    cod_engine: false,
    rcod: false,
    engine: COD_ENGINES.BASIC,
    cod_engine_type: COD_ENGINE_TYPES.SLAB_ELIGIBILITY,
  },
  fee_rules: [],
  item_categories: [],
  zones: [],
  validations: {
    fee_rules: true,
    zones: true,
    item_categories: true,
    mapping: true,
  },
};

export const magicCODSettingsReducer = (state = initialState, action) => {
  switch (action.type) {
    case ACTIONS.FETCH_CONFIG_SUCCESS: {
      const { data } = action.payload;
      const setForEditMode = action.setEditMode;
      const {
        configs,
        fee_rules = [],
        zones = [],
        editMode,
        item_categories,
        mappingValidation,
      } = formatResponse(data);
      return merge(state, {
        loading: {
          summary: false,
          fee_rules: false,
          zones: false,
          item_categories: false,
          mapping: false,
        },
        editMode: setForEditMode ? editMode : state.editMode,
        configs: setForEditMode ? configs : state.configs,
        zones: zones ?? [],
        fee_rules: fee_rules ?? [],
        item_categories: item_categories ?? [],
        validations: {
          fee_rules: true,
          zones: true,
          item_categories: true,
          mapping: mappingValidation,
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
        loading: { ...state.loading, fee_rules: false },
        validations: { ...state.validations, fee_rules: true },
      });
    case ACTIONS.UPDATE_FEE_RULE_SUCCESS: {
      const feeIdx = state.fee_rules.findIndex((f) => f.id === action.payload.data.id);
      const editedFeeRules = updateItem(state.fee_rules, feeIdx, action.payload.data);
      return merge(state, {
        ...state,
        fee_rules: editedFeeRules,
        loading: { ...state.loading, fee_rules: false },
      });
    }
    case ACTIONS.DELETE_FEE_RULE_SUCCESS: {
      const newFeeRules = remove(state.fee_rules, (item) => item.id === action.rule_id);
      return merge(state, {
        ...state,
        fee_rules: newFeeRules,
        loading: { ...state.loading, fee_rules: false },
      });
    }
    case ACTIONS.CLEAR_FEE_RULE_SUCCESS: {
      return merge(state, {
        ...state,
        fee_rules: [],
        loading: { ...state.loading, others: false },
      });
    }

    // ZONES
    case ACTIONS.CREATE_ZONE_SUCCESS: {
      const addedZones = push(state.zones, ...buildZonesData([action.payload.data]));
      return merge(state, {
        ...state,
        zones: addedZones,
        loading: { ...state.loading, zones: false },
        validations: { ...state.validations, zones: true },
      });
    }
    case ACTIONS.UPDATE_ZONE_SUCCESS: {
      const idx = state.zones.findIndex((z) => z.id === action.payload.data.id);
      const editedZones = updateItem(state.zones, idx, ...buildZonesData([action.payload.data]));
      return merge(state, {
        ...state,
        zones: editedZones,
        loading: { ...state.loading, zones: false },
      });
    }
    case ACTIONS.DELETE_ZONE_SUCCESS: {
      const newZones = remove(state.zones, (item) => item.id === action.zone_id);
      return merge(state, {
        ...state,
        zones: newZones,
        loading: { ...state.loading, zones: false },
      });
    }

    // CATEGORY
    case ACTIONS.CREATE_CATEGORY_SUCCESS: {
      const addedCategories = push(state.item_categories, action.payload.data);
      return merge(state, {
        ...state,
        item_categories: addedCategories,
        loading: { ...state.loading, item_categories: false },
        validations: { ...state.validations, item_categories: true },
      });
    }
    case ACTIONS.UPDATE_CATEGORY_SUCCESS: {
      const idx = state.item_categories.findIndex((z) => z.id === action.payload.data.id);
      const editedCategories = updateItem(state.item_categories, idx, action.payload.data);
      return merge(state, {
        ...state,
        item_categories: editedCategories,
        loading: { ...state.loading, item_categories: false },
      });
    }
    case ACTIONS.DELETE_CATEGORY_SUCCESS: {
      const newCategories = remove(state.item_categories, (item) => item.id === action.category_id);
      return merge(state, {
        ...state,
        item_categories: newCategories,
        loading: { ...state.loading, item_categories: false },
      });
    }

    case ACTIONS.MAP_FEE_RULE_SUCCESS:
    case ACTIONS.MAP_CATEGORIES_SUCCESS:
      return merge(state, { loading: { ...state.loading, mapping: false } });

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
      return merge(state, { loading: { ...state.loading, summary: true } });

    case ACTIONS.UPSERT_FEE_RULES_PENDING:
    case ACTIONS.UPDATE_FEE_RULE_PENDING:
    case ACTIONS.DELETE_FEE_RULE_PENDING:
    case ACTIONS.CLEAR_FEE_RULES_PENDING:
      return merge(state, { loading: { ...state.loading, fee_rules: true } });
    case ACTIONS.CREATE_ZONE_PENDING:
    case ACTIONS.UPDATE_ZONE_PENDING:
    case ACTIONS.DELETE_ZONE_PENDING:
      return merge(state, { loading: { ...state.loading, zones: true } });
    case ACTIONS.CREATE_CATEGORY_PENDING:
    case ACTIONS.UPDATE_CATEGORY_PENDING:
    case ACTIONS.DELETE_CATEGORY_PENDING:
      return merge(state, { loading: { ...state.loading, item_categories: true } });
    case ACTIONS.MAP_FEE_RULE_PENDING:
    case ACTIONS.MAP_CATEGORIES_PENDING:
      return merge(state, { loading: { ...state.loading, mapping: true } });
    case ACTIONS.FETCH_CONFIG_ERROR:
      return merge(state, initialState);
    case ACTIONS.UPSERT_FEE_RULES_ERROR:
    case ACTIONS.UPDATE_FEE_RULE_ERROR:
    case ACTIONS.DELETE_FEE_RULE_ERROR:
    case ACTIONS.CLEAR_FEE_RULES_ERROR:
    case ACTIONS.CREATE_ZONE_ERROR:
    case ACTIONS.UPDATE_ZONE_ERROR:
    case ACTIONS.DELETE_ZONE_ERROR:
    case ACTIONS.CREATE_CATEGORY_ERROR:
    case ACTIONS.UPDATE_CATEGORY_ERROR:
    case ACTIONS.DELETE_CATEGORY_ERROR:
    case ACTIONS.MAP_FEE_RULE_ERROR:
    case ACTIONS.MAP_CATEGORIES_ERROR:
      return merge(state, {
        loading: {
          summary: false,
          fee_rules: false,
          zones: false,
          item_categories: false,
          mapping: false,
        },
        error: action.payload?.error,
      });
    default:
      return state;
  }
};
