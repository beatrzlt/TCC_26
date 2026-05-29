<?php
$cursos = [
    ["nome" => "Curso 1", "tempo" => "0:45:55", "porcentagem" => 70],
    ["nome" => "Curso 2", "tempo" => "1:25:05", "porcentagem" => 90],
    ["nome" => "Curso 3", "tempo" => "0:03:00", "porcentagem" => 30],
    ["nome" => "Curso 4", "tempo" => "2:30:05", "porcentagem" => 80],
    ["nome" => "Curso 5", "tempo" => "0:10:01", "porcentagem" => 50],
];

$pesquisa = "";

if(isset($_GET["pesquisa"])) {
    $pesquisa = $_GET["pesquisa"];
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Andamento - Cursos</title>
  <link rel="stylesheet" href="style.css">
</head>

<body>

  <div class="container">

    <header class="topo">
      <h2 class="logo">METAL <span>Financeiro</span></h2>
    </header>

    <main class="conteudo">

      <form class="pesquisa" method="GET">
        <input type="text" name="pesquisa" placeholder="Pesquisa" value="<?php echo $pesquisa; ?>">
        <button type="submit">🔍</button>
      </form>

      <div class="aba">
        <p>Andamento</p>
      </div>

      <div class="lista-cursos">

        <?php foreach($cursos as $c) { ?>

          <?php 
            if($pesquisa != "" && stripos($c["nome"], $pesquisa) === false){
              continue;
            }
          ?>

          <div class="curso">
            <span class="nome"><?php echo $c["nome"]; ?></span>

            <div class="barra">
              <div class="progresso" style="width: <?php echo $c["porcentagem"]; ?>%"></div>
            </div>

            <span class="tempo"><?php echo $c["tempo"]; ?></span>
          </div>

        <?php } ?>

      </div>

      <a href="index.php" class="botao">← Voltar</a>

    </main>

  </div>

</body>
</html>