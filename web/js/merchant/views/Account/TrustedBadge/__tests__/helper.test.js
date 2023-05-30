import { mergeComponent, sortBy } from 'merchant/views/Account/TrustedBadge/helper';

describe('sortBy', () => {
  const allComponents = {
    component1: { id: 'component1', name: 'Component 1' },
    component2: { id: 'component2', name: 'Component 2' },
    component3: { id: 'component3', name: 'Component 3' },
  };

  it('should return an empty array if no order is provided', () => {
    const result = sortBy(allComponents);
    expect(result).toEqual([]);
  });

  it('should return an empty array if an empty order is provided', () => {
    const result = sortBy(allComponents, []);
    expect(result).toEqual([]);
  });

  it('should return an array of components in the specified order', () => {
    const order = ['component2', 'component1', 'component3'];
    const result = sortBy(allComponents, order);
    expect(result).toEqual([
      { id: 'component2', name: 'Component 2' },
      { id: 'component1', name: 'Component 1' },
      { id: 'component3', name: 'Component 3' },
    ]);
  });

  it('should filter out any components that are not in the order', () => {
    const order = ['component2', 'component1'];
    const result = sortBy(allComponents, order);
    expect(result).toEqual([
      { id: 'component2', name: 'Component 2' },
      { id: 'component1', name: 'Component 1' },
    ]);
  });

  it('should handle duplicate ids in the order', () => {
    const order = ['component2', 'component1', 'component2'];
    const result = sortBy(allComponents, order);
    expect(result).toEqual([
      { id: 'component2', name: 'Component 2' },
      { id: 'component1', name: 'Component 1' },
      { id: 'component2', name: 'Component 2' },
    ]);
  });

  it('should handle an order that includes ids not in allComponents', () => {
    const order = ['component2', 'component4', 'component1'];
    const result = sortBy(allComponents, order);
    expect(result).toEqual([
      { id: 'component2', name: 'Component 2' },
      { id: 'component1', name: 'Component 1' },
    ]);
  });
});

describe('mergeComponent', () => {
  const allComponents = {
    component1: {
      prop1: 'value1',
      prop2: 'value2',
    },
    component2: {
      prop3: 'value3',
      prop4: 'value4',
    },
  };

  const selectedData = {
    sections: {
      component1: {
        prop1: 'new value',
      },
      component3: {
        prop5: 'value5',
      },
    },
  };

  it('should merge selectedData with allComponents', () => {
    const result = mergeComponent(selectedData, allComponents);
    expect(result).toEqual({
      component1: {
        prop1: 'new value',
        prop2: 'value2',
      },
      component2: {
        prop3: 'value3',
        prop4: 'value4',
      },
      component3: {
        prop5: 'value5',
      },
    });
  });

  it('should add new component if it does not exist in allComponents', () => {
    const result = mergeComponent(selectedData, {});
    expect(result).toEqual({
      component1: {
        prop1: 'new value',
      },
      component3: {
        prop5: 'value5',
      },
    });
  });

  it('should return allComponents if selectedData.sections is undefined', () => {
    const result = mergeComponent({}, allComponents);
    expect(result).toEqual(allComponents);
  });

  it('should not modify allComponents if selectedData does not contain any components', () => {
    const result = mergeComponent({ sections: {} }, allComponents);
    expect(result).toEqual(allComponents);
  });
});
