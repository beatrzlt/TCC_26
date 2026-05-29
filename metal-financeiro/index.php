<?php
$curso = "Administração financeira";

$modulos = [
    ["nome" => "Módulo 1", "status" => "feito ✓", "classe" => "feito"],
    ["nome" => "Módulo 2", "status" => "não feito ✓", "classe" => "nao-feito"],
    ["nome" => "Estudo", "status" => "visto ^", "classe" => "cinza"],
    ["nome" => "Estudo", "status" => "não visto v", "classe" => "cinza"],
    ["nome" => "Lição", "status" => "não feito ^^", "classe" => "cinza"],
    ["nome" => "Módulo 3", "status" => "não feito v", "classe" => "nao-feito"],
    ["nome" => "Módulo 4", "status" => "não feito v", "classe" => "nao-feito"],
];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Curso - <?php echo $curso; ?></title>
  <link rel="stylesheet" href="style.css">
</head>

<body>

  <div class="container">

    <header class="topo">
      <h2 class="logo">METAL <span>Financeiro</span></h2>
    </header>

    <main class="conteudo">
      <h1 class="titulo">Curso</h1>
      <p class="subtitulo"><?php echo $curso; ?></p>

      <div class="lista">
        <?php foreach($modulos as $m) { ?>
          <div class="item <?php echo $m["classe"]; ?>">
            <span><?php echo $m["nome"]; ?></span>
            <span class="status"><?php echo $m["status"]; ?></span>
          </div>
        <?php } ?>
      </div>

      <a href="andamento.php" class="botao">Ir para Andamento →</a>

    </main>

  </div>

</body>
</html>