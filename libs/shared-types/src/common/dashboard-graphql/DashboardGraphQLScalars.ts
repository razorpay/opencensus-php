/** All built-in and custom scalars, mapped to their actual values */
export type DashboardGraphQLScalars = {
  ID: string;
  String: string;
  Boolean: boolean;
  Int: number;
  Float: number;
  BigInt: number;
  DateTime: Date;
  EmailAddress: string;
  JSON: { [key: string]: any };
  JSONObject: { [key: string]: any };
  NonNegativeInt: number;
  PositiveInt: number;
  URL: string;
  Upload: any;
  VPA: any;
};