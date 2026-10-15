; ==============================================================================
; Script de Inno Setup: JSBolsas Pro - Instalador Todo-en-Uno (Planta Física)
; Versión: 2.3.2
; Autor: Plásticos M&F Steel / Jhonny Pirela
; ==============================================================================

#define MyAppName "JSBolsas Pro"
#define MyAppVersion "2.3.2"
#define MyAppPublisher "Plasticos M&F Steel C.A."
#define MyAppURL "https://bolsas.plasticosmyf.com"
#define MyAppExeName "laragon.exe"
#define JSDeskPassword "1Avacamar1posa*7"

[Setup]
AppId={{8B23F45A-2A1C-4B9F-83C1-9988D43C1201}
AppName={#MyAppName}
AppVersion={#MyAppVersion}
AppPublisher={#MyAppPublisher}
AppPublisherURL={#MyAppURL}
AppSupportURL={#MyAppURL}
AppUpdatesURL={#MyAppURL}
DefaultDirName=C:\laragon
DisableDirPage=yes
DefaultGroupName={#MyAppName}
AllowNoIcons=yes
OutputDir=output
OutputBaseFilename=JSBolsasPro_Installer_v{#MyAppVersion}
Compression=lzma2/ultra64
SolidCompression=yes
WizardStyle=modern
PrivilegesRequired=admin
ArchitecturesInstallIn64BitMode=x64

[Languages]
Name: "spanish"; MessagesFile: "compiler:Languages\Spanish.isl"

[Tasks]
Name: "desktopicon"; Description: "Crear accesos directos en el Escritorio"; GroupDescription: "Accesos Directos:"; Flags: unchecked
Name: "autostart"; Description: "Iniciar servidor Laragon automáticamente al encender la PC"; GroupDescription: "Opciones de Sistema:"

[Files]
; 1. Archivos de Laragon Core (Apache, MySQL, PHP, etc.)
; Si empaquetas el directorio de Laragon local, descomenta la siguiente linea:
; Source: "C:\laragon\*"; DestDir: "{app}"; Flags: ignoreversion recursesubdirs createallsubdirs

; 2. Archivos del Sistema JSBolsas Pro
Source: "..\\*"; DestDir: "{app}\www\jsbolsas"; Flags: ignoreversion recursesubdirs createallsubdirs; Excludes: ".git\*,.github\*,storage\logs\*,node_modules\*,output\*"

; 3. Host Virtual de Apache para jsbolsas.test
Source: "vhost_template.conf"; DestDir: "{app}\etc\apache2\sites-enabled"; DestName: "auto.jsbolsas.test.conf"; Flags: ignoreversion

; 4. Script de Asistencia Post-Instalación
Source: "install_helper.bat"; DestDir: "{app}\www\jsbolsas\installer"; Flags: ignoreversion

; 5. Script de Actualización Automática
Source: "..\actualizar_jsbolsas.bat"; DestDir: "{app}\www\jsbolsas"; Flags: ignoreversion

[Icons]
; Acceso Directo a la Estación Web de Operarios / Báscula
Name: "{autodesktop}\🏭 JSBolsas Pro - Estación de Pesaje"; Filename: "{cmd}"; Parameters: "/c start http://jsbolsas.test/operario/estacion"; Tasks: desktopicon; Comment: "Abrir Estación de Pesaje y Báscula de JSBolsas Pro"

; Acceso Directo al Monitor y Finanzas
Name: "{autodesktop}\📊 JSBolsas Pro - Monitor de Planta"; Filename: "{cmd}"; Parameters: "/c start http://jsbolsas.test/dashboard"; Tasks: desktopicon; Comment: "Abrir Monitor de Planta y Rendimiento"

; Acceso Directo al Actualizador Seguro
Name: "{autodesktop}\🔄 Actualizar JSBolsas Pro"; Filename: "{app}\www\jsbolsas\actualizar_jsbolsas.bat"; Tasks: desktopicon; Comment: "Sincronizar y Actualizar JSBolsas Pro desde GitHub"

; Acceso Directo a Soporte Remoto (JSDesk)
Name: "{autodesktop}\🎧 Soporte Remoto (JSDesk)"; Filename: "{app}\tools\jsdesk\jsdesk.exe"; Tasks: desktopicon; Flags: createonlyiffileexists

; Inicio Automático de Laragon con Windows
Name: "{userstartup}\Laragon Servidor Industrial"; Filename: "{app}\laragon.exe"; Parameters: "--minimized"; Tasks: autostart

[Run]
; 1. Instalar y Configurar JSDesk como Servicio con Contraseña Fija Desatendida
Filename: "{app}\tools\jsdesk\jsdesk.exe"; Parameters: "--install-service"; Flags: runhidden waituntilterminated; Check: FileExists(ExpandConstant('{app}\tools\jsdesk\jsdesk.exe'))
Filename: "{app}\tools\jsdesk\jsdesk.exe"; Parameters: "--password {#JSDeskPassword}"; Flags: runhidden waituntilterminated; Check: FileExists(ExpandConstant('{app}\tools\jsdesk\jsdesk.exe'))

; 2. Instalar JSVPN (Red Privada Segura)
Filename: "{app}\tools\jsvpn\jsvpn.exe"; Parameters: "/quiet /norestart"; Flags: runhidden waituntilterminated; Check: FileExists(ExpandConstant('{app}\tools\jsvpn\jsvpn.exe'))

; 3. Inicializar Base de Datos, Migraciones y Configuración de Fábrica
Filename: "{app}\www\jsbolsas\installer\install_helper.bat"; Flags: runhidden waituntilterminated

; 4. Iniciar Laragon al finalizar instalación
Filename: "{app}\laragon.exe"; Description: "Iniciar servidor Laragon ahora"; Flags: nowait postinstall skipifsilent

[Code]
// Procedimiento para agregar '127.0.0.1 jsbolsas.test' al archivo hosts de Windows
procedure AddToHostsFile();
var
  HostsPath: string;
  HostsContent: TArrayOfString;
  I: Integer;
  EntryExists: Boolean;
begin
  HostsPath := ExpandConstant('{sys}\drivers\etc\hosts');
  EntryExists := False;

  if FileExists(HostsPath) then
  begin
    if LoadStringsFromFile(HostsPath, HostsContent) then
    begin
      for I := 0 to GetArrayLength(HostsContent) - 1 do
      begin
        if Pos('jsbolsas.test', HostsContent[I]) > 0 then
        begin
          EntryExists := True;
          Break;
        end;
      end;
    end;
  end;

  if not EntryExists then
  begin
    SetArrayLength(HostsContent, GetArrayLength(HostsContent) + 2);
    HostsContent[GetArrayLength(HostsContent) - 2] := '';
    HostsContent[GetArrayLength(HostsContent) - 1] := '127.0.0.1  jsbolsas.test';
    SaveStringsToFile(HostsPath, HostsContent, False);
  end;
end;

procedure CurStepChanged(CurStep: TSetupStep);
begin
  if CurStep = ssPostInstall then
  begin
    try
      AddToHostsFile();
    except
      // Manejo silencioso en caso de bloqueo estricto de antivirus
    end;
  end;
end;
