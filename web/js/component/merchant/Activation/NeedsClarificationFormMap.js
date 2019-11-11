export const getNeedsClarificationTabsData = (allFieldsMap, needsKyc) => {
  const allFieldsHash = {};
  const kycTabContent = [];
  for (let tab of allFieldsMap) {
    tab.forEach(f => {
      if (Array.isArray(f)) {
        for (let fitem of f) {
          allFieldsHash[fitem.name || fitem._name] = fitem;
        }
      } else {
        allFieldsHash[f.name || f._name] = f;
      }
    });
  }
  for (let field in needsKyc.clarification_reasons) {
    if (allFieldsHash[field]) {
      kycTabContent.push(allFieldsHash[field]);
    }
  }

  console.log(
    `Dynamically generate`,
    allFieldsMap,
    needsKyc,
    allFieldsHash,
    kycTabContent
  );
  return kycTabContent;
};
