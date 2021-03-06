param([string]$Site='mcock',                              # サイト名
      [string]$HostName='localhost',                      # ホスト名
      [string]$Port='5678',                               # ポート
      [string]$User='',                                   # ユーザー名
      [string]$Pass='',                                   # パスワード
      [string]$Builtin='LocalSystem',                     # ビルトインアカウント(Userを空にした場合に有効)
      [string]$DocumentRoot='web\contents',               # ドキュメントルート(※インテグルート以降)
      [string]$Protocol='http',                           # プロトコル
      [string]$CgiRoot='web\cgi-bin',                     # CGIルート(※インテグルート以降)
      [string]$CgiPath='web\php\7.1.24\php-cgi.exe',      # CGIパス(※インテグルート以降)
      [string]$CgiName='cgi-bin',                         # CGIエイリアス名
      [string]$CgiType='php',                             # CGI種別(php|perl|cgi)
      [string]$RewriteFrom='^api/(.*)',                   # URL Rewrite(From)
      [string]$RewriteTo='cgi-bin/index.php',             # URL Rewrite(To)
      [string]$StopSiteConflictPort='',                   # 同ポートの既存サイト停止(カンマ区切りで複数指定可能、エイリアス指定可能)
      [int]$MaxInstance=8,                                # 最大インスタンス数
      [switch]$SkipInstall,                               # IISインストールのスキップ
      [switch]$Proxy,                                     # Proxy設定
      [switch]$FastCGI,                                   # FastCGI設定
      [switch]$ErrorPage,                                 # エラーページ設定
      [switch]$RemoveProxy,                               # Proxy削除
      [switch]$RemoveSite,                                # サイト削除
      [switch]$Help)

if($script:Help){
    Write-Host "-----------------------------------------------------------"
    Write-Host " [Script]"
    Write-Host "   iis-setup.ps1"
    Write-Host " [Parameters]"
    Write-Host "   -Site <SiteName> : default='mcock'"
    Write-Host "   -HostName <HostName> : default='localhost'"
    Write-Host "   -Port <PortNumber> : default='5678'"
    Write-Host "   -User <UserName> : default=''"
    Write-Host "   -Pass <Password> : default=''"
    Write-Host "   -Builin <BuiltinAccountName> : default='LocalSystem'"
    Write-Host "   -DocumentRoot <Path> : default='web\Contents'"
    Write-Host "   -Protocol <Protocol> : default='http'"
    Write-Host "   -CgiRoot <Path> : default='web\cgi-bin'"
    Write-Host "   -CgiPath <Path> : default='web\php\7.1.24\php-cgi.exe'"
    Write-Host "   -CgiName <AliasName> : default='cgi-bin'"
    Write-Host "   -CgiType <Type> : default='php'"
    Write-Host "   -RewriteFrom <Pattern> : default='^api/(.*)'"
    Write-Host "   -RewriteTo <Pattern> : default='cgi-bin/index.php'"
    Write-Host "   -StopSiteConflictPort : default=''"
    Write-Host "   -MaxInstance : default=8"
    Write-Host "   -SkipInstall : Skip IIS install"
    Write-Host "   -RemoveSite : Remove Site"
    Write-Host "   -ErrorPage : Error page setup"
    Write-Host "-----------------------------------------------------------"
    exit 1
}

Set-Variable APPPOOL_NAME             $script:Site
Set-Variable USER_NAME                $script:User
Set-Variable PASSWORD                 $script:Pass
Set-Variable BUILTIN                  $script:Builtin
Set-Variable PROTOCOL                 $script:Protocol
Set-Variable CGI_PATH                 $script:CgiPath
Set-Variable CGI_NAME                 $script:CgiName
Set-Variable DOCUMENT_ROOT_PLANE      $script:DocumentRoot

Set-Variable WEBSITE_NAME             $script:Site
Set-Variable WEBSITE_HOST             $script:HostName
Set-Variable WEBSITE_PORT             $script:Port
Set-Variable WEBSITE_PORT_PLANE       $script:Port
Set-Variable WEBSITE_APPPOOL          "IIS:\AppPools\$script:Site"
Set-Variable WEBSITE_DEFAULT          $script:Site
Set-Variable WEBSITE_DEFAULT_PATH     "IIS:\Sites\$script:Site"
Set-Variable WEBSITE_DEFAULT_CGI      "$script:Site/$script:CgiName"
Set-Variable WEBSITE_DEFAULT_CGI_PATH "IIS:\Sites\$script:Site\$script:CgiName"
Set-Variable WEBSITE_FASTCGI_MAX_INST $script:MaxInstance

Set-Variable URL_REWRITE_MSI_PATH     '\system\shells\iis\rewrite_x64_ja-JP.msi'
Set-Variable URL_REWRITE_FROM         $script:RewriteFrom
Set-Variable URL_REWRITE_TO           $script:RewriteTo

Set-Variable ENV_DOCUMENT_ROOT        'SysDocumentRoot'

Set-Variable SITE_STOP_FORCE          $script:StopSiteConflictPort

# パス解決
Set-Variable CUR_DIR "$(Split-Path $myInvocation.MyCommand.Path -parent)\"
$drive = ($myInvocation.MyCommand.Path).Substring(0, 2)
$OPT_MCOCK_DIR = (Resolve-Path "$CUR_DIR\..\..")
$OPT_MCOCK_DIR = Convert-Path $OPT_MCOCK_DIR
$OPT_MCOCK_DIR_POSIX = $OPT_MCOCK_DIR -replace "\\", "/"
$DOCUMENT_ROOT = "$script:OPT_MCOCK_DIR\$script:DocumentRoot"
$DOCUMENT_ROOT = $DOCUMENT_ROOT -replace "/", "\"
$DOCUMENT_ROOT_POSIX = "$script:OPT_MCOCK_DIR/$script:DocumentRoot"
$DOCUMENT_ROOT_POSIX = $DOCUMENT_ROOT_POSIX -replace "\\", "/"
$DOCUMENT_ROOT_PLANE_POSIX = $DOCUMENT_ROOT_PLANE -replace "\\", "/"
$DOCUMENT_ROOT_PLANE_POSIX = "/$DOCUMENT_ROOT_PLANE_POSIX"
$CGI_ROOT = "$script:OPT_MCOCK_DIR\$script:CgiRoot"
$CGI_ROOT = $CGI_ROOT -replace "/", "\"
$CGI_FULL_PATH = "$script:OPT_MCOCK_DIR\$script:CGI_PATH"
$CGI_FULL_PATH = $CGI_FULL_PATH -replace "/", "\"
$URL_REWRITE_MSI_FULL_PATH = $script:OPT_MCOCK_DIR + $script:URL_REWRITE_MSI_PATH
$URL_REWRITE_MSI_FULL_PATH = $URL_REWRITE_MSI_FULL_PATH -replace "/", "\"

# CGI種別
if($script:CgiType -eq "php"){
    $CGI_TYPE = "*.php"
}elseif($script:CgiType -eq "perl"){
    $CGI_TYPE = "*.pl"
}elseif($script:CgiType -eq "cgi"){
    $CGI_TYPE = "*.cgi"
}else{
    Write-Host "<!> invalid cgi type($script:CgiType)"
    exit 1
}

# その他
try{
    Import-Module WebAdministration -ErrorAction Stop
    $script:WEBSITE_PORT = '*:' + $script:WEBSITE_PORT + ':'
    $script:WEBSITE_EXIST_ALREADY = ls IIS:\Sites | where {$_.Name -eq $script:WEBSITE_NAME}
}catch{
    # IIS本体がインストールされていない場合は無視
}

function install-iis()
{
    # IIS管理コンソール
    Write-Host "[1/12] Installing IIS-ManagementConsole" -ForegroundColor Cyan
    Dism /online /enable-feature /FeatureName:IIS-ManagementConsole /All | Out-Null

    # (CGI/サーバー側インクルードのインストールに必要)
    Write-Host "[2/12] Installing IIS-WebServer/ApplicationDevelopment" -ForegroundColor Cyan
    Dism /online /enable-feature /FeatureName:IIS-WebServer | Out-Null
    Dism /online /enable-feature /FeatureName:IIS-ApplicationDevelopment | Out-Null

    # CGI
    Write-Host "[3/12] Installing IIS-CGI" -ForegroundColor Cyan
    Dism /online /enable-feature /FeatureName:IIS-CGI | Out-Null

    # サーバー側インクルード
    Write-Host "[4/12] Installing IIS-ServerSideIncludes" -ForegroundColor Cyan
    Dism /online /enable-feature /FeatureName:IIS-ServerSideIncludes | Out-Null

    # URL Rewrite機能追加
    Write-Host "[5/12] Installing IIS-URL Rewrite" -ForegroundColor Cyan
    & msiexec /qn /i $URL_REWRITE_MSI_FULL_PATH

    # WebSocket
    Write-Host "[6/12] Installing IIS-WebSocket" -ForegroundColor Cyan
    Dism /online /enable-feature /FeatureName:IIS-WebSockets | Out-Null

    if([string]::IsNullOrEmpty($script:WEBSITE_PORT)){
        # 事前に取得できなかった場合の保険
        Import-Module WebAdministration -ErrorAction Stop
        $script:WEBSITE_PORT = '*:' + $script:WEBSITE_PORT + ':'
        $script:WEBSITE_EXIST_ALREADY = ls IIS:\Sites | where {$_.Name -eq $script:WEBSITE_NAME}
    }
}

function remove-site()
{
    # URL Rewrite削除
    if(Get-Website -Name $script:WEBSITE_NAME){
        if(Get-WebConfiguration -PSPath $script:WEBSITE_DEFAULT_PATH -Filter "/system.Webserver/rewrite/rules/rule[@name='$script:WEBSITE_NAME']"){        
            Clear-WebConfiguration -PSPath $script:WEBSITE_DEFAULT_PATH -Filter "/system.Webserver/rewrite/rules/rule[@name='$script:WEBSITE_NAME']"
        }
        if(Get-WebConfiguration -PSPath $script:WEBSITE_DEFAULT_PATH -Filter "/system.Webserver/rewrite/rules/rule[@name='SPA']"){
            Clear-WebConfiguration -PSPath $script:WEBSITE_DEFAULT_PATH -Filter "/system.Webserver/rewrite/rules/rule[@name='SPA']"
        }
    }

    # Webサイト削除
    if(Get-Website -Name $script:WEBSITE_NAME){
        Remove-WebSite -Name $script:WEBSITE_NAME
    }

    # アプリケーションプール削除
    if(Test-Path $script:WEBSITE_APPPOOL){
        Remove-Item $script:WEBSITE_APPPOOL -recurse -force
    }

    # FastCGI削除
    $fastCGIPath = Get-WebConfiguration "/system.Webserver/fastcgi/application" | Where-Object { $_.fullPath -eq $CGI_FULL_PATH }
    if($fastCGIPath){
        Clear-WebConfiguration "/system.Webserver/fastcgi/application[@fullpath='$CGI_FULL_PATH']"
    }
}

function remove-proxy()
{
    if(Get-WebConfiguration -Filter "/system.Webserver/rewrite/globalRules/rule[@name='$script:WEBSITE_NAME']"){
        Clear-WebConfiguration -Filter "/system.Webserver/rewrite/globalRules/rule[@name='$script:WEBSITE_NAME']"
    }
}

function set-ssl([bool]$in_create)
{
    if($script:PROTOCOL -eq 'https'){
        if(!(Get-WebBinding -Name $script:WEBSITE_NAME -Protocol https -Port $script:WEBSITE_PORT_PLANE)){
            if($in_create){ 
                New-Item IIS:\Sites\$script:WEBSITE_NAME -bindings @{protocol=$script:PROTOCOL;bindingInformation=$script:WEBSITE_PORT} -force | Out-Null
                # 一旦削除(なぜかNew-ItemをしないとNew-WebBindingが失敗する)
                Remove-WebBinding -Name $script:WEBSITE_NAME -BindingInformation $script:WEBSITE_PORT | Out-Null
            }
            if([String]::IsNullOrEmpty($script:WEBSITE_HOST)){
                New-WebBinding -Name $script:WEBSITE_NAME -Protocol https -Port $script:WEBSITE_PORT_PLANE -SslFlags 0 | Out-Null
                $cert = Get-ChildItem Cert:\LocalMachine\My | Where-Object{ $_.FriendlyName -eq "IIS Express Development Certificate" }  # 本チャン環境ではFridndlyNameは正式対応すること
                try{ Remove-Item -Path "IIS:\SslBindings\!$script:WEBSITE_PORT_PLANE" -ErrorAction:Stop | Out-Null } catch{}
                try{ New-Item -Path "IIS:\SslBindings\!$script:WEBSITE_PORT_PLANE" -Value $cert -SslFlags 0 -ErrorAction:Stop | Out-Null } catch{}
            }else{
                New-WebBinding -Name $script:WEBSITE_NAME -Protocol https -HostHeader $script:WEBSITE_HOST -Port $script:WEBSITE_PORT_PLANE -SslFlags 1 | Out-Null
                $cert = Get-ChildItem Cert:\LocalMachine\My | Where-Object{ $_.FriendlyName -eq "IIS Express Development Certificate" }  # 本チャン環境ではFridndlyNameは正式対応すること
                try{ Remove-Item -Path "IIS:\SslBindings\!$script:WEBSITE_PORT_PLANE!$script:WEBSITE_HOST" -ErrorAction:Stop | Out-Null } catch{}
                try{ New-Item -Path "IIS:\SslBindings\!$script:WEBSITE_PORT_PLANE!$script:WEBSITE_HOST" -Value $cert -SslFlags 1 -ErrorAction:Stop | Out-Null } catch{}
            }
        }
    }
}

function setup-iis()
{
    # IIS操作モジュールの読み込み
    Import-Module WebAdministration | Out-Null
    
    # Webサイト作成
    Write-Host "[7/12] Setting IIS-CreateSite" -ForegroundColor Cyan
    if($script:WEBSITE_EXIST_ALREADY){
        $exist = $False
        $regex = [regex]"[^*^:]+"
        $ports = @()
        $siteBindings = $script:WEBSITE_EXIST_ALREADY.Bindings.Collection.bindingInformation
        foreach($siteBinding in $siteBindings){
            $regex.Matches($siteBinding) | foreach{
                $ports += $_.Value
            }
        }
        foreach($port in $ports){
            if($script:WEBSITE_PORT_PLANE -eq $port){
                $exist = $True
            }
        }
        if(!$exist){
            if($script:PROTOCOL -eq 'https'){ set-ssl }
            else{ New-WebBinding -Name $script:WEBSITE_DEFAULT -Protocol $script:PROTOCOL -Port $script:WEBSITE_PORT_PLANE -force | Out-Null }
        }
    }else{
        if($script:PROTOCOL -eq 'https'){ set-ssl $True }
        else{ New-Item IIS:\Sites\$script:WEBSITE_DEFAULT -bindings @{protocol=$script:PROTOCOL;bindingInformation=$script:WEBSITE_PORT} -force | Out-Null }
    }

    # 特定できないCGIモジュールを許可する
    Write-Host "[8/12] Setting IIS-Home" -ForegroundColor Cyan
    Set-WebConfiguration /system.Webserver/Security/isapiCgiRestriction/@notListedCgisAllowed -Value True -PSPath "MACHINE/WEBROOT/APPHOST" | Out-Null

    # アプリケーションプールの追加/設定
    Write-Host "[9/12] Setting IIS-ApplicationPool" -ForegroundColor Cyan
    if(!(Test-Path $script:WEBSITE_APPPOOL)){
        New-Item $script:WEBSITE_APPPOOL -Force | Out-Null
        $AppPool = Get-Item $script:WEBSITE_APPPOOL
        if([string]::IsNullOrEmpty($script:USER_NAME)){
            $AppPool.processModel.identityType = $script:BUILTIN
        }else{
            $AppPool.processModel.identityType = "SpecificUser"
            $AppPool.processModel.userName = $script:USER_NAME
            $AppPool.processModel.password = $script:PASSWORD
        }
        $AppPool | Set-Item | Out-Null
    }

    # Webサイト設定
    Write-Host "[10/12] Setting IIS-WebSite" -ForegroundColor Cyan
    Set-ItemProperty -Path $script:WEBSITE_DEFAULT_PATH -Name PhysicalPath -Value $DOCUMENT_ROOT | Out-Null
    Set-ItemProperty -Path $script:WEBSITE_DEFAULT_PATH -Name ApplicationPool -Value $script:APPPOOL_NAME | Out-Null
    & $Env:WinDir\system32\inetsrv\appcmd.exe unlock config $script:WEBSITE_DEFAULT -section:handlers /commitpath:apphost | Out-Null    # ロック解除が必要らしい
    & $Env:WinDir\system32\inetsrv\appcmd.exe set config $script:WEBSITE_DEFAULT -section:system.webServer/security/authentication/anonymousAuthentication /enabled:"True" /userName: /commit:apphost | Out-Null
    & $Env:WinDir\system32\inetsrv\appcmd.exe set config $script:WEBSITE_DEFAULT -section:system.webServer/handlers /accessPolicy:"Read,Script" | Out-Null
    & $Env:WinDir\system32\inetsrv\appcmd.exe set config $script:WEBSITE_DEFAULT -section:staticContent /+"[fileExtension='.dmg',mimeType='application/x-apple-diskimage']" | Out-Null
    & $Env:WinDir\system32\inetsrv\appcmd.exe set config $script:WEBSITE_DEFAULT -section:staticContent /+"[fileExtension='.jdf',mimeType='text/xml']" | Out-Null
    & $Env:WinDir\system32\inetsrv\appcmd.exe set config $script:WEBSITE_DEFAULT -section:requestfiltering /requestlimits.maxallowedcontentlength:4294967295 | Out-Null
    if($script:ErrorPage){ Set-WebConfigurationProperty -PSPath $script:WEBSITE_DEFAULT_PATH -Filter "/system.webServer/httpErrors" -Name "errorMode" -Value "DetailedLocalOnly" }
    else{ Set-WebConfigurationProperty -PSPath $script:WEBSITE_DEFAULT_PATH -Filter "/system.webServer/httpErrors" -Name "errorMode" -Value "Detailed" }    
    if(!(Get-WebConfiguration -PSPath $script:WEBSITE_DEFAULT_PATH -Filter "/system.Webserver/httpProtocol/customHeaders/add[@name='Access-Control-Allow-Origin']")){
        Add-WebConfigurationProperty -PSPath $script:WEBSITE_DEFAULT_PATH -Filter "/system.Webserver/httpProtocol/customHeaders" -AtIndex 0 -Name collection -Value @{name='Access-Control-Allow-Origin';value='*'}
    }
    if(!(Get-WebConfiguration -PSPath $script:WEBSITE_DEFAULT_PATH -Filter "/system.Webserver/httpProtocol/customHeaders/add[@name='Access-Control-Allow-Headers']")){
        Add-WebConfigurationProperty -PSPath $script:WEBSITE_DEFAULT_PATH -Filter "/system.Webserver/httpProtocol/customHeaders" -AtIndex 0 -Name collection -Value @{name='Access-Control-Allow-Headers';value='*'}
    }
    if(!(Get-WebConfiguration -PSPath $script:WEBSITE_DEFAULT_PATH -Filter "/system.Webserver/httpProtocol/customHeaders/add[@name='Access-Control-Allow-Methods']")){
        Add-WebConfigurationProperty -PSPath $script:WEBSITE_DEFAULT_PATH -Filter "/system.Webserver/httpProtocol/customHeaders" -AtIndex 0 -Name collection -Value @{name='Access-Control-Allow-Methods';value='*'}
    }
    if(!(Get-WebConfiguration -PSPath $script:WEBSITE_DEFAULT_PATH -Filter "/system.Webserver/httpProtocol/customHeaders/add[@name='Access-Control-Expose-Headers']")){
        Add-WebConfigurationProperty -PSPath $script:WEBSITE_DEFAULT_PATH -Filter "/system.Webserver/httpProtocol/customHeaders" -AtIndex 0 -Name collection -Value @{name='Access-Control-Expose-Headers';value='*'}
    }

    # CGI設定
    Write-Host "[11/12] Setting IIS-CGI" -ForegroundColor Cyan
    if(!(Get-WebApplication -Name $script:CGI_NAME -Site $script:WEBSITE_DEFAULT)){
        New-WebApplication -Name $script:CGI_NAME -Site $script:WEBSITE_DEFAULT -PhysicalPath $CGI_ROOT -ApplicationPool $script:APPPOOL_NAME | Out-Null
        & $Env:WinDir\system32\inetsrv\appcmd.exe unlock config $script:WEBSITE_DEFAULT_CGI -section:handlers /commitpath:apphost | Out-Null    # ロック解除が必要らしい
        & $Env:WinDir\system32\inetsrv\appcmd.exe set config $script:WEBSITE_DEFAULT_CGI -section:system.webServer/security/authentication/anonymousAuthentication /enabled:"True" /userName: /commit:apphost | Out-Null
        & $Env:WinDir\system32\inetsrv\appcmd.exe set config $script:WEBSITE_DEFAULT_CGI -section:system.webServer/handlers /accessPolicy:"Script,Execute" | Out-Null
        & $Env:WinDir\system32\inetsrv\appcmd.exe set config $script:WEBSITE_DEFAULT_CGI -section:system.webServer/cgi /createProcessAsUser:"False" /commit:apphost | Out-Null
        & $Env:WinDir\system32\inetsrv\appcmd.exe set config $script:WEBSITE_DEFAULT_CGI -section:system.webServer/cgi /timeout:"00:15:00" /commit:apphost | Out-Null
    }
    if($script:FastCGI){
        $CGIModuleSetting = "FastCgiModule"
        $fastCGIPath = Get-WebConfiguration "/system.Webserver/fastcgi/application" | Where-Object { $_.fullPath -eq $CGI_FULL_PATH }
        if(!$fastCGIPath){
            Add-WebConfiguration "/system.Webserver/fastcgi" -Value @{fullpath=$CGI_FULL_PATH}
            Set-WebConfigurationProperty "/system.Webserver/fastcgi/application[@fullpath='$CGI_FULL_PATH']" -Name maxInstances -Value $script:WEBSITE_FASTCGI_MAX_INST
            Set-WebConfigurationProperty "/system.Webserver/fastcgi/application[@fullpath='$CGI_FULL_PATH']" -Name idleTimeout -Value 900
            Set-WebConfigurationProperty "/system.Webserver/fastcgi/application[@fullpath='$CGI_FULL_PATH']" -Name activityTimeout -Value 900
            Set-WebConfigurationProperty "/system.Webserver/fastcgi/application[@fullpath='$CGI_FULL_PATH']" -Name requestTimeout -Value 900
        }
    }
    else { $CGIModuleSetting = "CgiModule" }
    if(Get-WebHandler -PSPath $script:WEBSITE_DEFAULT_CGI_PATH -Name $script:CGI_NAME){
        Set-WebConfigurationProperty -PSPath $script:WEBSITE_DEFAULT_CGI_PATH -Filter "/system.Webserver/handlers/add[@name='$script:CGI_NAME']" -Name "Modules" -Value $CGIModuleSetting | Out-Null
        Set-WebConfigurationProperty -PSPath $script:WEBSITE_DEFAULT_CGI_PATH -Filter "/system.Webserver/handlers/add[@name='$script:CGI_NAME']" -Name "Verb" -Value 'HEAD,GET,POST,PUT,PATCH,DELETE' | Out-Null
    }else{
        New-WebHandler -PSPath $script:WEBSITE_DEFAULT_CGI_PATH -Name $script:CGI_NAME -Path $script:CGI_TYPE -Verb 'HEAD,GET,POST,PUT,PATCH,DELETE' -Modules $CGIModuleSetting -ScriptProcessor $CGI_FULL_PATH | Out-Null	
    }    
    if(Get-WebConfiguration "/system.Webserver/handlers/add[@name='CGI-exe']" -PSPath $script:WEBSITE_DEFAULT_CGI_PATH){
        Clear-WebConfiguration "/system.Webserver/handlers/add[@name='CGI-exe']" -PSPath $script:WEBSITE_DEFAULT_CGI_PATH
    }

    # URL Rewrite設定
    Write-Host "[12/12] Setting IIS-URL Rewrite" -ForegroundColor Cyan
    if(!(Get-WebConfiguration -PSPath $script:WEBSITE_DEFAULT_PATH -Filter "/system.Webserver/rewrite/rules/rule[@name='$script:WEBSITE_NAME']")){
        Add-WebConfiguration -PSPath $script:WEBSITE_DEFAULT_PATH -Filter "/system.Webserver/rewrite/rules" -Value @{name=$script:WEBSITE_NAME; patternSyntax='Regular Expressions'; stopProcessing='False'}
        Set-WebConfigurationProperty -PSPath $script:WEBSITE_DEFAULT_PATH -Filter "/system.Webserver/rewrite/rules/rule[@name='$script:WEBSITE_NAME']/match" -Name "url" -Value $script:URL_REWRITE_FROM
        Set-WebConfigurationProperty -PSPath $script:WEBSITE_DEFAULT_PATH -Filter "/system.Webserver/rewrite/rules/rule[@name='$script:WEBSITE_NAME']/action" -Name "type" -Value "Rewrite"
        Set-WebConfigurationProperty -PSPath $script:WEBSITE_DEFAULT_PATH -Filter "/system.Webserver/rewrite/rules/rule[@name='$script:WEBSITE_NAME']/action" -Name "url" -Value $script:URL_REWRITE_TO
    }
    if(!(Get-WebConfiguration -PSPath $script:WEBSITE_DEFAULT_PATH -Filter "/system.Webserver/rewrite/rules/rule[@name='SPA']")){
        Add-WebConfiguration -PSPath $script:WEBSITE_DEFAULT_PATH -Filter "/system.Webserver/rewrite/rules" -Value @{name='SPA'; patternSyntax='Regular Expressions'; stopProcessing='True'}
        Set-WebConfigurationProperty -PSPath $script:WEBSITE_DEFAULT_PATH -Filter "/system.Webserver/rewrite/rules/rule[@name='SPA']/match" -Name "url" -Value ".*"
        Add-WebConfigurationProperty -PSPath $script:WEBSITE_DEFAULT_PATH -Filter "/system.Webserver/rewrite/rules/rule[@name='SPA']/conditions" -Name "." -Value @{input='{REQUEST_FILENAME}'; matchType='IsFile'; negate='True'}
        Add-WebConfigurationProperty -PSPath $script:WEBSITE_DEFAULT_PATH -Filter "/system.Webserver/rewrite/rules/rule[@name='SPA']/conditions" -Name "." -Value @{input='{REQUEST_FILENAME}'; matchType='IsDirectory'; negate='True'}
        Add-WebConfigurationProperty -PSPath $script:WEBSITE_DEFAULT_PATH -Filter "/system.Webserver/rewrite/rules/rule[@name='SPA']/conditions" -Name "." -Value @{input='{REQUEST_URI}'; pattern='^/(api)'; negate='True'}
        Set-WebConfigurationProperty -PSPath $script:WEBSITE_DEFAULT_PATH -Filter "/system.Webserver/rewrite/rules/rule[@name='SPA']/action" -Name "type" -Value "Rewrite"
        Set-WebConfigurationProperty -PSPath $script:WEBSITE_DEFAULT_PATH -Filter "/system.Webserver/rewrite/rules/rule[@name='SPA']/action" -Name "url" -Value "/"
    }
}

function setup-proxy()
{
    if(Get-WebConfigurationProperty -Filter system.Webserver/proxy -Name Enabled){
        Set-WebConfigurationProperty -Filter system.webServer/proxy -Name Enabled -Value $True
        if(!(Get-WebConfiguration -Filter "/system.Webserver/rewrite/globalRules/rule[@name=$script:WEBSITE_NAME]")){
            Add-WebConfiguration -Filter "/system.Webserver/rewrite/globalRules" -Value @{name=$script:WEBSITE_NAME; patternSyntax='Regular Expressions'; stopProcessing='True'}
            Set-WebConfigurationProperty -Filter "/system.Webserver/rewrite/globalRules/rule[@name='$script:WEBSITE_NAME']/match" -Name "url" -Value $script:URL_REWRITE_FROM
            Set-WebConfigurationProperty -Filter "/system.Webserver/rewrite/globalRules/rule[@name='$script:WEBSITE_NAME']/action" -Name "type" -Value "Rewrite"
            Set-WebConfigurationProperty -Filter "/system.Webserver/rewrite/globalRules/rule[@name='$script:WEBSITE_NAME']/action" -Name "url" -Value $script:URL_REWRITE_TO
        }
    }
}

function stop-site()
{
    # Web Site停止
    $sites = $script:SITE_STOP_FORCE -split ","
    if(![String]::IsNullOrEmpty($sites)){
        foreach($site in $sites){
            $sites = ls IIS:\Sites | where {$_.Name -like $site}
            foreach($site in $sites){
                if(![String]::IsNullOrEmpty($site)){
                    $regex = [regex]"[^*^:]+"
                    $siteName = $site.Name
                    $ports = @()
                    $siteBindings = $site.Bindings.Collection.bindingInformation
                    foreach($siteBinding in $siteBindings){
                        $regex.Matches($siteBinding) | foreach {
                            $ports += $_.Value
                        }
                    }
                    foreach($port in $ports){
                        if($script:WEBSITE_PORT_PLANE -eq $port){
                            Stop-WebSite -Name $siteName
                        }
                    }
                }
            }
        }
    }
}

function main()
{
    try{
        if($script:SkipInstall){
            Write-Host "Skip install..."
        }elseif($script:Proxy){
            # Proxy設定
            setup-proxy
            exit 0
        }elseif($script:RemoveProxy){
            # Proxy削除
            remove-proxy
            exit 0
        }elseif($script:RemoveSite){
            # サイト削除
            remove-site
            exit 0
        }else{
            # IISインストール
            install-iis
        }

        # 同ポートの既存サイト停止
        $sites = $script:SITE_STOP_FORCE -split ","
        if(![String]::IsNullOrEmpty($sites)){ stop-site }

        # IIS設定
        setup-iis
    }catch{
        Write-host $error -ForegroundColor Red
        Write-Host "Failed."
        exit 1
    }

    Write-Host "Completed successfully."
}

# エントリーポイント
main
