interface updateNameSuccessResponseType {
  status_code: number;
  success: boolean;
  data: {
    id: string;
    name: string;
  };
}

export const updateNameSuccessResponse: updateNameSuccessResponseType = {
  status_code: 200,
  success: true,
  data: {
    id: 'test123446',
    name: 'Kunal Test',
  },
};
