import { cacheManager } from '../cache';
import { openMultipleTerminalsWithCommands } from './openMultipleTerminalsWithCommands';
import { promptUserForLocalRemotes } from './prompts/promptUserForLocalRemotes';
import { checkPortInUse } from './checkPortInUse';
import { promptToKillConflictingPIDs } from './prompts/promptToKillConflictingPIDs';

function handleError(error: Error) {
  if (!error.message.includes('User force closed')) {
    cacheManager.cleanUp();
    console.log(error);
    console.error('Error:', error.message);
  }
}

async function handleUserSelection() {
  try {
    const tasksMapArr = await promptUserForLocalRemotes();
    const portsInUse = await checkPortInUse(tasksMapArr.map((task) => task.serverPort));

    if (portsInUse) await promptToKillConflictingPIDs(portsInUse);

    await openMultipleTerminalsWithCommands(tasksMapArr);

    process.exit(0);
  } catch (error: any) {
    handleError(error);
  }
}

handleUserSelection();
