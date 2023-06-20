import {
  entityAttributes,
  searchableEntities,
} from 'merchant/components/HeaderNav/UniversalSearch/configs';
import {
  EntityAttributeIdsTypes,
  attributeType,
  SearchableEntities,
  SearchableEntityType,
  ProductType,
} from 'merchant/components/HeaderNav/UniversalSearch/typings';

export function entitySearch(searchQuery: string): {
  success: boolean;
  results: ProductType[];
} {
  const entityAttributesKeys = Object.keys(entityAttributes);
  const matchedEntities: SearchableEntities[] = [];
  const matchedAttribute: EntityAttributeIdsTypes[] = [];
  let matchedAttributeType = '';

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
  matchedAttribute: EntityAttributeIdsTypes[],
  matchedAttributeType: string,
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

export function makeQuery(
  result: SearchableEntityType,
  searchQuery: string,
  matchedAttribute: EntityAttributeIdsTypes[],
  matchedAttributeType: string,
): string {
  const entityAttributes = result.attributes;

  let queryParam: string | undefined;
  // uniquely identified the attribute
  if (matchedAttribute.length === 1) {
    if (matchedAttribute[0] === 'ph_number' && result.id === 'Payments') {
      let countryCode = '+91';
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
      }=${value}`;
    } else {
      queryParam = entityAttributes[matchedAttribute[0]];
      return `${result.route}?${queryParam}=${searchQuery}`;
    }
  }

  // multiple attributes have matched
  if (matchedAttributeType === 'entity_state') {
    const entityAttributes = result.attributes;
    for (let i = 0; i <= matchedAttribute.length; i++) {
      if (entityAttributes[matchedAttribute[i]]) {
        queryParam = entityAttributes[matchedAttribute[i]];
        return `${result.route}?${queryParam}=${searchQuery}`;
      }
    }
  }

  // search failed to determine entity attribute
  return `${result.route}?q=${searchQuery}`;
}
