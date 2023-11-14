<?php

/**
 * @author Alejandro Martínez Domínguez
 */

// Importa el archivo Jugador.php para acceder a su objeto
require_once 'Jugador.php';

// Comienza una session para poder utilizar la función $_SESSION y pasar valores
// de variables entre distintos bloques
session_start();

?>

<!DOCTYPE html>
<html lang="es">
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>UNO</title>
        <!-- Etiquetas link para importar la fuente "Lato" de Google -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Lato&display=swap" rel="stylesheet">
        <!-- Enlace a la hoja de estilos -->
        <link rel="stylesheet" href="css/style.css">
    </head>
    <body>
        <h1>Bienvenid@ a <span id="unoLogo">UNO</span></h1>
        <form method="post">
            <label for="playerCount">Número de Jugadores: </label>
            <input id="playerCount" name="playerCount" type="number" min="2" max="10" value="2"><br>
            <input id="play" name="play" type="submit" value="NUEVA PARTIDA">
        </form>

<?php

// Ruta del archivo json guardado en una variable
$cardsFile = "json/cards.json"; 

// Obtiene el archivo con el nombre de $cardsFile
$jsonCardsFile = file_get_contents($cardsFile);

// Decodifica el archivo json de cartas y lo guarda en la variable cards
$cards = json_decode($jsonCardsFile, true);

// Cuando se pulsa Play
if(isset($_POST["play"])){
    
    // Comprueba si la lectura fue correcta o no, y que exista un número de jugadores
    if (!empty($cards) && !empty($_POST["playerCount"])) {
        echo "Lectura Correcta";

        // Formulario dinámico, según la cantidad de jugadores indicada, se mostrarán
        // mas o menos inputs para introducir el nombre de cada uno de los usuarios
        echo '<form method="post">';
        for ($i = 1; $i <= $_POST["playerCount"]; $i++) {
            echo "<label for='nombre_jugador_$i'>Nombre del Jugador $i: </label>";
            echo "<input id='nombre_jugador_$i' name='nombre_jugador_$i' type='text'>";
            echo "<input id='playerCount' name='playerCount' type='text' value='" . $_POST['playerCount'] . "' hidden>";
            echo "<br>";
        }
        echo '<input type="submit" name="savePlayers" id="savePlayers" value="Guardar Jugadores">';
        echo '</form>';
        
    } else {
        echo "<span id='error'>ERROR <br> Lectura fallida o sin número de jugadores</span>";
    }
}

// Cuando se pulsa sobre el botón de guardar jugadores
if(isset($_POST["savePlayers"])){
    
    // Se definen distintas variables inicializadas como arrays
    $jugadores = array();
    $cartas = array();
    $mesa = array();

    // Bucle que se ejecuta una vez por cada jugador que existe, a este
    // se le añade a un objeto jugador, con su nombre y cartas
    for ($i = 1; $i <= $_POST["playerCount"]; $i++) {
        
        $nombre = $_POST["nombre_jugador_$i"];
        
        $jugador = new Jugador($nombre);
        
        shuffle($cards['cards']); // Mezcla todos los elementos de cartas
        
        $cartas = array_slice($cards['cards'], 0, 7); // Extrae las primeras 7 cartas
        $cards['cards'] = array_slice($cards['cards'], 7); // Elimina las 7 primeras cartas
        
        // Guarda en el jugador la mano de cartas
        $jugador->setMano($cartas);
        
        // Juarda el jugador actual en el array de jugadores
        $jugadores[] = $jugador;
    }
    
    // Variable isPlaying que indica el número de jugador actual,
    // 0 siendo equivalente al jugador 1
    $isPlaying = 0;
    
    // Variable isBlack, por defecto inicializada a true
    $isBlack = true;
    
    // Mientras la carta sea negra ejecuta el bucle, siempre lo va a ejecutar
    // mínimo una vez para añadir una carta a la mesa, por inicializar la variable
    // isBlack en true
    while($isBlack){
            
        // Mezcla la baraja y mete su primera carta en la primera
        // posición del array mesa
        shuffle($cards['cards']);
        array_unshift($mesa, $cards['cards'][0]);
        
        // Borra la carta de la baraja
        unset($cards['cards'][0]); 
            
        // Si la carta de encima de todo de la mesa no es negra,
        // isBlack se marca false, y por lo tanto sale del bucle
        if ($mesa[0]['color'] != "negro") {
           $isBlack = false;
        }
        
    }

    // Guardado de variables session para poder acceder a ellas
    // desde cualquier sitio del archivo php
    $_SESSION["jugadores"] = $jugadores;
    $_SESSION["isPlaying"] = $isPlaying;
    $_SESSION["mesa"] = $mesa;
    $_SESSION["cards"] = $cards;

    echo "<br>";

    partida();
      
}

function partida(){
    
    // Cuando la cantidad de cartas en la baraja sea menor que 2, llama a la
    // función refill que recoge las cartas de la mesa y las guarda de nuevo
    // en la baraja
    if(count($_SESSION["cards"]["cards"]) < 2){
        refill();
    }
    
    // Obtiene el jugador actual y lo guarda en una variable session
    $jugadorActual = $_SESSION["jugadores"][$_SESSION["isPlaying"]];
    $_SESSION["jugadorActual"] = $jugadorActual;

    // Si aun no hay ganador, se muestra los datos del jugador actual, y su
    // mano con unos radiobuttons para poder marcar la carta
    if(!winner()){
 
        echo "Jugador: " . $jugadorActual->getNombre() . "<br>";
        echo '<form method="post">';
        foreach ($jugadorActual->getMano() as $carta) {
            echo "<input name='carta' value= '". $carta['id'] ."' type='radio' '>";
            echo "<label for'card'>Color: ". $carta['color'] . " | Número: " . $carta['number'] . "</label> <br>";
        }

        echo "<br>";
        echo '<input type="submit" name="putCard" id="putCard" value="Poner Carta">';
        echo '<input type="submit" name="newCard" id="newCard" value="Robar Carta"';
        echo '</form>';
        echo "<br>";

        // Muestra las cartas sobre la mesa
        echo "Cartas de la mesa: <br>";
        for ($i = 0; $i < count($_SESSION["mesa"]); $i++) {
            echo "Color: " . $_SESSION["mesa"][$i]['color'] .  " | Número: " . $_SESSION["mesa"][$i]['number'] . "<br>";
        }

        // Cuenta el número de cartas en la baraja
        echo '<br>' . "Cantidad de cartas en baraja: " . count($_SESSION["cards"]["cards"]);

        // Si winner() devuelve true, muestra un mensaje de victoria.
        }else{
            echo '¡¡¡EL GANADOR ES: ' . $_SESSION["jugadorActual"]->getNombre() . '!!!';
        }
 
}

// Cuando se pulsa sobre el botón de poner carta
if(isset($_POST["putCard"])){
    
    // Si coinciden el color o el número de la carta seleccionada con la carta
    // de la mesa
    if(colorAndNumberCheck()){
        
        $_SESSION["isPlaying"]++; // Incrementa el índice del jugador
        
        if ($_SESSION["isPlaying"] >= count($_SESSION["jugadores"])) {
            $_SESSION["isPlaying"] = 0; // Vuelve al primer jugador si llega al final de la lista
        }

        // Guarda la mano del jugador en una variable para poder manipularla
        $oldMano = $_SESSION["jugadorActual"]->getMano();

        // Bucle que se recorre todas las cartas de la mano
        foreach ($_SESSION["jugadorActual"]->getMano() as $key => $card) {

            // Si el id de la carta actual coincide con el de la carta seleccionada en el formulario
            if($card["id"]==$_POST["carta"]){

                array_unshift($_SESSION["mesa"], $card); // Añade la carta a la mesa desde arriba
                unset($_SESSION["cards"]["cards"][$card["id"]]); // Elimina la carta de la baraja

                unset($oldMano[$key]); // Elimina de la mano la carta con el valor de key

                // Se guarda la carta seleccionada en una variable session
                $_SESSION["selectedCard"] = $card; 

            }

        }

        // Se guarda la mano modificada en el jugador actual
        $_SESSION["jugadorActual"]->setMano($oldMano);

        // Llamada a función para gestionar las cartas especiales
        specialCardCheck();
     }
     else{
        // Si el color o numero no coinciden, se vuelve a mostrar la pantalla
        // de selección de carta del jugador actual
        partida();
    }

    
}

// Al pulsar el botón de robar carta
if(isset($_POST["newCard"])){

    // Mezca el array de cartas y guarda en $newCard la primera
    shuffle($_SESSION["cards"]["cards"]);
    $newCard = $_SESSION["cards"]["cards"][0];
    
    // Obtiene la mano del jugador actual
    $oldMano = $_SESSION["jugadorActual"]->getMano();
    
    // Añade la carta nueva a la mano desde arriba
    array_unshift($oldMano, $newCard);
    
    // Guarda la nueva mano en el jugador actual
    $_SESSION["jugadorActual"]->setMano($oldMano);
    
    // Elimina la carta de la baraja
    unset($_SESSION["cards"]["cards"][$newCard["id"]]);
    
    $_SESSION["isPlaying"]++; // Incrementa el índice del jugador

        if ($_SESSION["isPlaying"] >= count($_SESSION["jugadores"])) {
            $_SESSION["isPlaying"] = 0; // Vuelve al primer jugador si llega al final de la lista
        }
    
    partida();

}

/**
 * Función que comprueba si la carta actual es especial, y si lo es, actua
 * en consecuencia
 */
function specialCardCheck(){
    
    // Mano del siguiente jugador (Es el siguiente porque esta función se ejecuta
    // al final de la función de poner carta, por lo que ya está sumado el isPlaying)
    $oldMano = $_SESSION["jugadores"][$_SESSION["isPlaying"]]->getMano();
    $number = $_SESSION["selectedCard"]["number"];
    
    // Entra en el if si es string, filtro necesario para que no entren en el
    // switch y actuen los números naturales como índices, y por lo tanto
    // hagan funciones de cartas especiales
    if(is_string($number)){
        
        switch($number){
        case "+2":
            // Si la carta es +2, añade dos cartas aleatorias a la mano
            // del siguiente jugador
            for ($i = 0; $i < 2; $i++) {
                
                shuffle($_SESSION["cards"]["cards"]);
                $newCard = $_SESSION["cards"]["cards"][0];
                
                array_unshift($oldMano, $newCard);
                
                // Elimina la carta de la baraja
                unset($_SESSION["cards"]["cards"][$newCard["id"]]);
                
            }
            
            // Guarda la nueva mano
            $_SESSION["jugadores"][$_SESSION["isPlaying"]]->setMano($oldMano);

            break;
        
        case "+4":
            // Si la carta es +4, añade dos cartas aleatorias a la mano
            // del siguiente jugador
            for ($i = 0; $i < 4; $i++) {
                
                shuffle($_SESSION["cards"]["cards"]);
                $newCard = $_SESSION["cards"]["cards"][0];
                
                array_unshift($oldMano, $newCard);
                
                // Elimina la carta de la baraja
                unset($_SESSION["cards"]["cards"][$newCard["id"]]);
                
            }
            
            // Guarda la nueva mano
            $_SESSION["jugadores"][$_SESSION["isPlaying"]]->setMano($oldMano);
            
            // Llamada a función para seleccionar nuevo color
            selectNewColor();
            
            break;
        
        // Se salta un turno
        case "prohibido":

            $_SESSION["isPlaying"]++; // Incrementa el índice del jugador
    
            if ($_SESSION["isPlaying"] >= count($_SESSION["jugadores"])) {
                $_SESSION["isPlaying"] = 0; // Vuelve al primer jugador si llega al final de la lista
            }
            
            break;
        
        case "cambio de sentido":
            // Invierte el array de jugadores, para por lo tanto, invertir su
            // sentido
            $reversePlayerArray = array_reverse($_SESSION["jugadores"]);
            $_SESSION["jugadores"] = $reversePlayerArray;
            break;
        
        case "comodin":
            // Llamada a función para seleccionar nuevo color
            selectNewColor();
            break;
        
        default:
            break;
        }
    }

    partida();
    
}

/**
 * Función que comprueba que la carta seleccionada sea igual en número o color
 * a la carta posicionada encima de la mesa.
 */
function colorAndNumberCheck(){
    
    foreach ($_SESSION["jugadorActual"]->getMano() as $key => $card) {

            if($card["id"]==$_POST["carta"]){
                
                if($card["color"]==$_SESSION["mesa"][0]["color"] || $card["number"]==$_SESSION["mesa"][0]["number"] || $card["color"]=="negro"){
                
                    return true;      
                }
            }            
    }
    return false;  
}

/**
 * Función que muestra un formulario para seleccionar un nuevo
 * color con el que jugar.
 */
function selectNewColor(){
    
    echo "
        
    <div id='newColor'>
    
    <form method='post'>

        <p>Selecciona el color a cambiar:</p>

        <input name='carta' value='rojo' type='radio'>
        <label>Rojo</label>

        <input name='carta' value='azul' type='radio'>
        <label>Azul</label>

        <input name='carta' value='verde' type='radio'>
        <label>Verde</label>

        <input name='carta' value='amarillo' type='radio'>
        <label>Amarillo</label>

        <input id='newColor' name='newColor' type='submit' value='Cambiar Color'>

    </form>
    
    </div> 
    ";

}

// Si se pulsa sobre el botón de cambiar color...
if(isset($_POST["newColor"])){
    
    // Modifica el color de la carta de encima de la mesa, por el selccionado
    $_SESSION["mesa"][0]["color"] = $_POST["carta"];
    
    partida();
}

/**
 * Función que comprueba si la mano del jugador actual está vacía, si lo
 * está, significa que el jugador ha ganado, por lo que devuelve true.
 */
function winner(){
    if(empty($_SESSION["jugadorActual"]->getMano())){
        return true;
    }
    return false;
}

/**
 * Añade todas las cartas de la mesa a la baraja, mezclándolas
 * con anterioridad.
 */
function refill() {
    
    shuffle($_SESSION["mesa"]);

    foreach ($_SESSION["mesa"] as $carta) {

        array_unshift($_SERVER["cards"]["cards"], $carta);
        unset($_SESSION["mesa"][$carta]);
    }
}

?>
    </body>
</html>