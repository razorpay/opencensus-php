const gstinDetails = {
  defaultGstin: '29AADCB2230M1ZP',
  gstinList: ['29AADCB2230M1ZP', '32AADCB2230M1Z2', '27AADCB2230M1ZT', '23AADCB2230M1Z1'],
};

const gstinData = { ...gstinDetails };

function read() {
  return gstinData;
}

export { read };
