export function addTableAria() {
  try {
    const allTables = document.querySelectorAll('table');
    const allCaptions = document.querySelectorAll('caption');
    const allRowGroups = document.querySelectorAll('thead, tbody, tfoot');
    const allRows = document.querySelectorAll('tr');
    const allCells = document.querySelectorAll('td');
    const allHeaders = document.querySelectorAll('th');
    const allRowHeaders = document.querySelectorAll('th[scope=row]');

    for (let i = 0; i < allTables.length; i++) {
      allTables[i].setAttribute('role', 'table');
    }

    for (let i = 0; i < allCaptions.length; i++) {
      allCaptions[i].setAttribute('role', 'caption');
    }

    for (let i = 0; i < allRowGroups.length; i++) {
      allRowGroups[i].setAttribute('role', 'rowgroup');
    }

    for (let i = 0; i < allRows.length; i++) {
      allRows[i].setAttribute('role', 'row');
    }

    for (let i = 0; i < allCells.length; i++) {
      allCells[i].setAttribute('role', 'cell');
    }

    for (let i = 0; i < allHeaders.length; i++) {
      allHeaders[i].setAttribute('role', 'columnheader');
    }

    for (let i = 0; i < allRowHeaders.length; i++) {
      allRowHeaders[i].setAttribute('role', 'rowheader');
    }
  } catch (e) {
    console.error("addTableAria(): ", e);
  }
}
