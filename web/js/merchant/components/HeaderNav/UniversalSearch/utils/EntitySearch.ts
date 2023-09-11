import moment from 'moment';

import {
  entityAttributes,
  searchableEntities,
  defaultQueryParamsPerEntity,
  statusKeywordsStore,
} from 'merchant/components/HeaderNav/UniversalSearch/configs';
import {
  attributeType,
  SearchableEntities,
  SearchableEntityType,
  ProductType,
  EntityAttributeTypes,
  AttributeType,
} from 'merchant/components/HeaderNav/UniversalSearch/typings';

// **** Constants ****
const DEFAULT_DATE_RANGE_IN_DAYS = 30;
const DEFAULT_COUNTRY_CODE = '+91';
const COMMON_ENTITY_ATTRIBUT_TYPES: AttributeType[] = ['entity_state', 'entity_url'];
// *****

// **** Main search util for entities
export function entitySearch(searchQuery: string): {
  success: boolean;
  results: ProductType[];
} {
  const entityAttributesKeys = Object.keys(entityAttributes);
  const matchedEntities: SearchableEntities[] = [];
  const matchedAttribute: EntityAttributeTypes[] = [];
  let matchedAttributeType: string | AttributeType = '';

  for (let i = 0; i < entityAttributesKeys.length; i++) {
    const attribute: attributeType = entityAttributes[entityAttributesKeys[i]];

    if (!Array.isArray(attribute.matchWith)) {
      const attrRegex = new RegExp(attribute.matchWith);
      if (attrRegex.test(searchQuery.toLowerCase())) {
        matchedEntities.push(...attribute.entities);
        matchedAttribute.push(attribute.attributeId);
        matchedAttributeType = attribute.attributeType;
      }
    } else {
      // eslint-disable-next-line no-lonely-if
      if (attribute.matchWith.includes(searchQuery.toLowerCase())) {
        console.log('1.', attribute.matchWith.includes(searchQuery.toLowerCase()), attribute);
        matchedEntities.push(...attribute.entities);
        matchedAttribute.push(attribute.attributeId);
        matchedAttributeType = attribute.attributeType;
      }
    }
  }

  const transformedSearchResults = transformEntitySearchResults(
    searchQuery,
    matchedEntities,
    matchedAttribute,
    matchedAttributeType,
  );

  return {
    success: matchedEntities.length !== 0,
    results: transformedSearchResults,
  };
}

export function transformEntitySearchResults(
  searchQuery: string,
  results: SearchableEntities[],
  matchedAttribute: EntityAttributeTypes[],
  matchedAttributeType: string | AttributeType,
): ProductType[] {
  const intermediateView: SearchableEntityType[] = [];
  const entitiesLookedUp = {};

  if (results.length > 0) {
    results.forEach((result) => {
      if (!entitiesLookedUp[result]) {
        intermediateView.push(searchableEntities[result]);
        entitiesLookedUp[result] = true;
      }
    });
  } else {
    Object.keys(searchableEntities).forEach((entityKey) => {
      intermediateView.push(searchableEntities[entityKey]);
    });
  }

  const finalResults: ProductType[] = [];

  intermediateView.forEach((entity) => {
    const query = makeQuery(entity, searchQuery, matchedAttribute, matchedAttributeType);

    finalResults.push({
      item: {
        id: entity.id,
        title: searchQuery,
        url: `${query}`,
        icon: entity.icon,
        attributes: entity.attributes,
        group: [`in: ${entity.id}`],
        tags: [],
      },
    });
  });

  return finalResults;
}

export const getDefaultDateRangeForPayments = (): { to: number; from: number } => {
  const now = moment();
  const to = now.startOf('D').unix(); // to
  const from = now.startOf('D').subtract(DEFAULT_DATE_RANGE_IN_DAYS, 'days').endOf('D').unix(); // from

  return { to, from };
};

export function makeQuery(
  result: SearchableEntityType,
  searchQuery: string,
  matchedAttribute: EntityAttributeTypes[],
  matchedAttributeType: string | AttributeType,
): string {
  const entityAttributes = result.attributes;

  let queryParam: string | undefined;
  // uniquely identified the attribute
  if (matchedAttribute.length === 1) {
    if (result.id === 'Payments') {
      const { to, from } = getDefaultDateRangeForPayments();

      if (matchedAttribute[0] === 'PhoneNumber') {
        let countryCode = DEFAULT_COUNTRY_CODE;
        let number: string | undefined;

        if (searchQuery?.charAt(0) === '+') {
          countryCode = searchQuery.substring(0, 3);
          number = searchQuery.substring(3, 13);
        } else if (queryParam?.charAt(0) === '0') {
          number = searchQuery.substring(1, 11);
        }
        const value = number ? number : searchQuery;
        return `${result.route}?country_code=${countryCode}&${
          entityAttributes[matchedAttribute[0]]
        }=${value}&from=${from}&to=${to}`;
      } else {
        queryParam = entityAttributes[matchedAttribute[0]];
        return `${result.route}?${queryParam}=${searchQuery}&from=${from}&to=${to}`;
      }
    } else {
      queryParam = entityAttributes[matchedAttribute[0]];

      if ((matchedAttributeType as AttributeType) === 'entity_state') {
        const queryParamValue = statusKeywordsStore[result.id][searchQuery];
        return `${result.route}?${queryParam}=${queryParamValue}`;
      }

      return `${result.route}?${queryParam}=${searchQuery}`;
    }
  }

  // multiple attributes have matched
  if (COMMON_ENTITY_ATTRIBUT_TYPES.includes(matchedAttributeType as AttributeType)) {
    const entityAttributes = result.attributes;
    for (let i = 0; i <= matchedAttribute.length; i++) {
      if (entityAttributes[matchedAttribute[i]]) {
        queryParam = entityAttributes[matchedAttribute[i]];
        if ((matchedAttributeType as AttributeType) === 'entity_state') {
          const queryParamValue = statusKeywordsStore[result.id][searchQuery];
          return `${result.route}?${queryParam}=${queryParamValue}`;
        } else {
          return `${result.route}?${queryParam}=${searchQuery}`;
        }
      }
    }
  }

  // search failed to determine entity attribute
  const genericParam = defaultQueryParamsPerEntity[result.id];
  return `${result.route}?${genericParam}=${searchQuery}`;
}
