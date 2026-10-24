const { app, BrowserWindow } = require('electron');

const appUrl = process.env.ECOBANTAY_URL || 'https://ecobantaysystem.page.gd/';

function createWindow() {
  const win = new BrowserWindow({
    width: 1280,
    height: 800,
    webPreferences: {
      contextIsolation: true,
      nodeIntegration: false,
      sandbox: true
    }
  });

  win.webContents.on('did-finish-load', function () {
    const path = new URL(win.webContents.getURL()).pathname.replace(/\/+$/, '');
    if (path === '/admin/settings.php' || path === '/admin/about.php') {
      win.webContents.insertCSS(
        '.admin-main > .card { width: 100% !important; max-width: none !important; }'
      ).catch(function (error) {
        console.error('Could not align the admin content card.', error);
      });
    }
  });

  win.loadURL(appUrl).catch(function (error) {
    console.error('Could not load the EcoBantay hosting site:', error);
  });
}

app.whenReady().then(createWindow);
app.on('window-all-closed', function () {
  app.quit();
});
