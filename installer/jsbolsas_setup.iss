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
ArchitecturesInstallIn64BitMode=x64compatible
ArchitecturesAllowed=x64compatible

[Languages]
Name: "spanish"; MessagesFile: "compiler:Languages\Spanish.isl"

[Tasks]
Name: "desktopicon"; Description: "Crear accesos directos en el Escritorio"; GroupDescription: "Accesos Directos:"
Name: "laragon"; Description: "Instalar entorno de servidor web Laragon WAMP oficial"; GroupDescription: "Componentes Adicionales de Servidor y Red:"
Name: "jsdesk"; Description: "Instalar cliente de soporte remoto JSDesk"; GroupDescription: "Componentes Adicionales de Servidor y Red:"
Name: "jsvpn"; Description: "Instalar cliente de red segura JSVPN Enterprise"; GroupDescription: "Componentes Adicionales de Servidor y Red:"
Name: "autostart"; Description: "Iniciar servidor Laragon automáticamente al encender la PC"; GroupDescription: "Opciones de Sistema:"

[Files]
; 1. Herramientas oficiales que se extraen temporalmente y se eliminan tras la instalación
Source: "tools\laragon-wamp.exe"; DestDir: "{tmp}"; Flags: deleteafterinstall; Tasks: laragon; Check: NotLaragonInstalled
Source: "tools\jsdesk_setup.exe"; DestDir: "{tmp}"; Flags: deleteafterinstall; Tasks: jsdesk; Check: NotJSDeskInstalled
Source: "tools\JSVPN-Setup.exe"; DestDir: "{tmp}"; Flags: deleteafterinstall; Tasks: jsvpn; Check: NotJSVPNInstalled

; 2. Archivos del Sistema JSBolsas Pro
Source: "..\\*"; DestDir: "{app}\www\jsbolsas"; Flags: ignoreversion recursesubdirs createallsubdirs; Excludes: ".git\*,.github\*,storage\logs\*,node_modules\*,mobile_bolsas_app\build\*,mobile_bolsas_app\.dart_tool\*,installer\tools\*,installer\output\*"

; 3. Host Virtual de Apache para jsbolsas.test
Source: "vhost_template.conf"; DestDir: "{app}\etc\apache2\sites-enabled"; DestName: "auto.jsbolsas.test.conf"; Flags: ignoreversion

; 4. Script de Asistencia Post-Instalación
Source: "install_helper.bat"; DestDir: "{app}\www\jsbolsas\installer"; Flags: ignoreversion
Source: "setup.ps1"; DestDir: "{app}\www\jsbolsas\installer"; Flags: ignoreversion

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
Name: "{autodesktop}\🎧 Soporte Remoto (JSDesk)"; Filename: "{code:GetJSDeskExePath}"; Tasks: desktopicon; Check: IsJSDeskInstalled; Comment: "Abrir Soporte Remoto JSDesk"

; Inicio Automático de Laragon con Windows
Name: "{userstartup}\Laragon Servidor Industrial"; Filename: "{app}\laragon.exe"; Parameters: "--minimized"; Tasks: autostart

[Run]
; 1. Instalar Laragon WAMP si la tarea está marcada y no está instalado previamente
Filename: "{tmp}\laragon-wamp.exe"; Parameters: "/SILENT /NORESTART /SUPPRESSMSGBOXES"; Flags: runhidden waituntilterminated; Tasks: laragon; Check: NotLaragonInstalled; StatusMsg: "Instalando entorno oficial de servidor Laragon WAMP..."

; 2. Instalar JSDesk SOLO si la tarea está marcada y no está instalado previamente
Filename: "{tmp}\jsdesk_setup.exe"; Parameters: "--silent-install"; Flags: runhidden waituntilterminated; Tasks: jsdesk; Check: NotJSDeskInstalled; StatusMsg: "Instalando servicio de soporte remoto JSDesk..."

; 3. Configurar la clave maestra de soporte desatendido en JSDesk (se ejecuta si JSDesk está presente en el equipo)
Filename: "{code:GetJSDeskExePath}"; Parameters: "--password {#JSDeskPassword}"; Flags: runhidden waituntilterminated; Check: IsJSDeskInstalled; StatusMsg: "Configurando clave maestra de soporte en JSDesk..."

; 4. Instalar JSVPN (Red Privada Segura) solo si la tarea está marcada y no está instalado
Filename: "{tmp}\JSVPN-Setup.exe"; Parameters: "/VERYSILENT /NORESTART /SUPPRESSMSGBOXES"; Flags: runhidden waituntilterminated; Tasks: jsvpn; Check: NotJSVPNInstalled; StatusMsg: "Instalando cliente seguro JSVPN Enterprise..."

; 5. Inicializar Base de Datos, Migraciones y Configuración de Fábrica
Filename: "{app}\www\jsbolsas\installer\install_helper.bat"; Flags: runhidden waituntilterminated; StatusMsg: "Inicializando base de datos y configuraciones limpias de fábrica..."

; 6. Iniciar Laragon al finalizar instalación
Filename: "{app}\laragon.exe"; Description: "Iniciar servidor Laragon ahora"; Flags: nowait postinstall skipifsilent

[Code]
// Detección de JSDesk instalado en 64 o 32 bits
function GetJSDeskExePath(Param: string): string;
begin
  if FileExists(ExpandConstant('{autopf}\JSDesk\JSDesk.exe')) then
    Result := ExpandConstant('{autopf}\JSDesk\JSDesk.exe')
  else if FileExists(ExpandConstant('{autopf32}\JSDesk\JSDesk.exe')) then
    Result := ExpandConstant('{autopf32}\JSDesk\JSDesk.exe')
  else if FileExists('C:\Program Files\JSDesk\JSDesk.exe') then
    Result := 'C:\Program Files\JSDesk\JSDesk.exe'
  else if FileExists('C:\Program Files (x86)\JSDesk\JSDesk.exe') then
    Result := 'C:\Program Files (x86)\JSDesk\JSDesk.exe'
  else
    Result := '';
end;

function IsJSDeskInstalled(): Boolean;
begin
  Result := (GetJSDeskExePath('') <> '');
end;

function NotJSDeskInstalled(): Boolean;
begin
  Result := not IsJSDeskInstalled();
end;

// Detección de Laragon
function IsLaragonInstalled(): Boolean;
begin
  Result := FileExists(ExpandConstant('{app}\laragon.exe')) or FileExists('C:\laragon\laragon.exe');
end;

function NotLaragonInstalled(): Boolean;
begin
  Result := not IsLaragonInstalled();
end;

// Detección de JSVPN
function IsJSVPNInstalled(): Boolean;
begin
  Result := FileExists(ExpandConstant('{autopf}\JSVPN\jsvpn.exe')) or
            FileExists('C:\Program Files\JSVPN\jsvpn.exe');
end;

function NotJSVPNInstalled(): Boolean;
begin
  Result := not IsJSVPNInstalled();
end;

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
