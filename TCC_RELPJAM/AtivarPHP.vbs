Const ForReading = 1
Const ForWriting = 2

arquivo = "C:\xampp\php\php.ini"

Set fso = CreateObject("Scripting.FileSystemObject")
Set arq = fso.OpenTextFile(arquivo, ForReading)

texto = arq.ReadAll
arq.Close

texto = Replace(texto, ";extension=pdo_pgsql", "extension=pdo_pgsql")
texto = Replace(texto, ";extension=pdo_sqlite", "extension=pdo_sqlite")
texto = Replace(texto, ";extension=pgsql", "extension=pgsql")

Set arq = fso.OpenTextFile(arquivo, ForWriting)
arq.Write texto
arq.Close

MsgBox "Pronto! Extensões ativadas.", 64, "PHP"