import fs from 'fs';
import path from 'path';
import { DASHBOARD_ROOT } from '../constants';

/**
 * A utility class to handle JSON file operations in a specified directory.
 */
export class CacheManager {
  private filePath: string;

  /**
   * @param directory - The directory where the JSON file should be stored.
   * @param fileName - The name of the JSON file (default is 'data.json').
   */
  constructor(directory: string, fileName: string = 'dashboard-cli.cache.json') {
    // Ensure the directory exists, if not, create it
    if (!fs.existsSync(directory)) {
      fs.mkdirSync(directory, { recursive: true });
    }

    // Set the file path
    this.filePath = path.join(directory, fileName);
  }

  /**
   * Initializes the JSON file. If it exists, cleanUp is called to delete the old file.
   * If the file does not exist, it creates a new one.
   * @returns {void}
   */
  onInit(): void {
    if (fs.existsSync(this.filePath)) {
      console.log(`\nCleaning up previous cache...`);
      this.cleanUp();
    }

    // Create an empty JSON file
    fs.writeFileSync(this.filePath, JSON.stringify({}), 'utf8');
    console.log(`Initialized new cache.`);
  }

  /**
   * Saves data to the JSON file. If the file doesn't exist, it throws an error.
   * @param data - The data to be saved to the JSON file. Must be an object.
   * @returns {void}
   */
  saveData(data: Record<string, any>): void {
    try {
      if (!fs.existsSync(this.filePath)) {
        throw new Error(`Cache does not exist: ${this.filePath}\n`);
      }

      const currentData: Record<string, any> = this.readData() || {};
      const updatedData: Record<string, any> = { ...currentData, ...data };

      fs.writeFileSync(this.filePath, JSON.stringify(updatedData, null, 2), 'utf8');
      console.log(
        `Selections saved. Refer ${path.relative(DASHBOARD_ROOT, this.filePath)} if needed.\n`,
      );
    } catch (error) {
      throw new Error(`Error saving selections. ${(error as Error).message}\n`);
    }
  }

  /**
   * Reads the data from the JSON file.
   * @returns {Record<string, any> | null} - The content of the JSON file as an object, or null if the file does not exist or is empty.
   */
  readData(): Record<string, any> | null {
    try {
      if (!fs.existsSync(this.filePath)) {
        console.error(`Cache not found: ${path.relative(DASHBOARD_ROOT, this.filePath)}`);
        return null;
      }

      const data: string = fs.readFileSync(this.filePath, 'utf8');
      return JSON.parse(data || '{}');
    } catch (error) {
      console.error(`Error reading cache: ${(error as Error).message}\n`);
      return null;
    }
  }

  /**
   * Deletes the JSON file if it exists.
   * @returns {void}
   */
  cleanUp(): void {
    try {
      if (fs.existsSync(this.filePath)) {
        fs.unlinkSync(this.filePath);
      }
    } catch (error) {
      console.error(`Error deleting previously used cache. ${(error as Error).message}\n`);
    }
  }
}
