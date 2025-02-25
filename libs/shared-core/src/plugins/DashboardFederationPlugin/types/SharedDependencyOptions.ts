export interface SharedDependencyOptions {
  singleton?: boolean;
  strictVersion?: boolean;
  requiredVersion?: string;
  eager?: boolean;
  version?: string;
  import?: boolean | string;
  shareKey?: string;
  shareScope?: string;
}

export interface ModuleFederationShared {
  [dependency: string]: SharedDependencyOptions | string; // Dependency name as the key, options or a string (for simple versioning).
}
