<?php
session_start();
include("includes/conexion.php");

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

// Inicializar carrito si no existe
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

// Procesar acciones del carrito
if (isset($_POST['accion'])) {
    switch ($_POST['accion']) {
        case 'agregar':
            $coche_id = (int)$_POST['coche_id'];
            $cantidad = isset($_POST['cantidad']) ? (int)$_POST['cantidad'] : 1;
            
            // Verificar si el coche ya está en el carrito
            $existe = false;
            foreach ($_SESSION['carrito'] as &$item) {
                if ($item['id'] == $coche_id) {
                    $item['cantidad'] += $cantidad;
                    $existe = true;
                    break;
                }
            }
            
            if (!$existe) {
                // Obtener detalles del coche
                $sql = "SELECT * FROM coches WHERE id = $coche_id";
                $result = $conn->query($sql);
                if ($result->num_rows > 0) {
                    $coche = $result->fetch_assoc();
                    $_SESSION['carrito'][] = [
                        'id' => $coche['id'],
                        'modelo' => $coche['modelo'],
                        'precio' => $coche['precio'],
                        'imagen' => $coche['imagen'],
                        'cantidad' => $cantidad
                    ];
                }
            }
            break;
            
        case 'actualizar':
            foreach ($_POST['cantidad'] as $id => $cantidad) {
                $id = (int)$id;
                $cantidad = (int)$cantidad;
                
                foreach ($_SESSION['carrito'] as &$item) {
                    if ($item['id'] == $id) {
                        if ($cantidad > 0) {
                            $item['cantidad'] = $cantidad;
                        } else {
                            // Eliminar si la cantidad es 0
                            $_SESSION['carrito'] = array_filter($_SESSION['carrito'], function($item) use ($id) {
                                return $item['id'] != $id;
                            });
                        }
                        break;
                    }
                }
            }
            break;
            
        case 'eliminar':
            $coche_id = (int)$_POST['coche_id'];
            $_SESSION['carrito'] = array_filter($_SESSION['carrito'], function($item) use ($coche_id) {
                return $item['id'] != $coche_id;
            });
            break;
            
        case 'comprar':
            // Registrar la compra en la base de datos
            $usuario_id = $_SESSION['usuario']['id'];
            foreach ($_SESSION['carrito'] as $item) {
                $coche_id = $item['id'];
                $cantidad = $item['cantidad'];
                
                // Insertar cada coche comprado
                for ($i = 0; $i < $cantidad; $i++) {
                    $sql = "INSERT INTO compras (usuario_id, coche_id) VALUES ($usuario_id, $coche_id)";
                    $conn->query($sql);
                }
            }
            
            // Vaciar el carrito
            $_SESSION['carrito'] = [];
            $mensaje = "¡Compra realizada con éxito!";
            break;
    }
}

// Variables de búsqueda y filtro
$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
$precio_min = isset($_GET['precio_min']) ? (float)$_GET['precio_min'] : '';
$precio_max = isset($_GET['precio_max']) ? (float)$_GET['precio_max'] : '';

// Construcción de la consulta SQL con filtro de búsqueda y precio
$sql = "SELECT * FROM coches WHERE 1";

// Filtro de búsqueda por modelo
if ($busqueda != '') {
    $sql .= " AND modelo LIKE '%" . $conn->real_escape_string($busqueda) . "%'";
}

// Filtro de precio
if ($precio_min != '' && $precio_max != '') {
    $sql .= " AND precio BETWEEN $precio_min AND $precio_max";
} elseif ($precio_min != '') {
    $sql .= " AND precio >= $precio_min";
} elseif ($precio_max != '') {
    $sql .= " AND precio <= $precio_max";
}

$sql .= " LIMIT 12 OFFSET 0"; // Cambiar el OFFSET según la página

$coches = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tienda de Coches</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #1a2a4a;
            --secondary-color: #0d6efd;
            --accent-color: #0b5ed7;
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .sidebar {
            background: linear-gradient(to bottom, var(--primary-color), #0a1a3e);
            min-height: 100vh;
            color: white;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
        }
        
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            border-radius: 5px;
            margin: 5px 0;
            transition: all 0.3s;
        }
        
        .sidebar .nav-link:hover {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .sidebar .nav-link.active {
            color: white;
            background-color: var(--accent-color);
        }
        
        .navbar {
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .card {
            transition: transform 0.3s, box-shadow 0.3s;
            border: none;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .card-img-top {
            height: 180px;
            object-fit: cover;
        }
        
        .btn-primary {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
        }
        
        .btn-success {
            background-color: #28a745;
            border-color: #28a745;
        }
        
        .badge {
            font-size: 0.7rem;
        }
        
        .carrito-item {
            border-bottom: 1px solid #eee;
            padding: 10px 0;
        }
        
        .carrito-item:last-child {
            border-bottom: none;
        }
        
        .carrito-total {
            font-weight: bold;
            font-size: 1.2rem;
            color: var(--primary-color);
        }
        
        .logo-img {
            max-width: 150px;
            filter: brightness(0) invert(1);
        }
        
        .alert-fixed {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
            animation: fadeIn 0.5s, fadeOut 0.5s 2.5s forwards;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes fadeOut {
            from { opacity: 1; transform: translateY(0); }
            to { opacity: 0; transform: translateY(20px); }
        }
    </style>
</head>
<body>

<div class="d-flex">
    <!-- Sidebar izquierdo con logo -->
    <div class="sidebar p-4" style="width: 250px;">
        <div class="d-flex justify-content-center mb-4">
            <img src="./img/logo.jpg" class="logo-img" alt="Logo empresa">
        </div>
        <h5 class="text-center mb-4">Bienvenido, <span class="fw-bold"><?= htmlspecialchars($_SESSION['usuario']['nombre']) ?></span></h5>
        <hr>
        <nav class="nav flex-column">
            <a class="nav-link active" href="./index.php"><i class="bi bi-house-door me-2"></i>Inicio</a>
            <a class="nav-link" href="perfil.php"><i class="bi bi-person me-2"></i>Perfil</a>
            <a class="nav-link" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Cerrar sesión</a>
        </nav>
    </div>

    <!-- Contenido principal -->
    <div class="flex-fill">
        <!-- Navbar superior con icono de carrito -->
        <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
            <div class="container-fluid">
                <span class="navbar-brand fw-bold text-primary">Tienda de Coches</span>
                <div class="ms-auto d-flex align-items-center">
                    <form class="d-flex me-3" method="GET" action="coches.php">
                        <div class="input-group">
                            <input class="form-control" type="search" placeholder="Buscar coche" aria-label="Buscar" name="busqueda" value="<?= htmlspecialchars($busqueda) ?>">
                            <input class="form-control" style="max-width: 120px;" type="number" placeholder="Mín" aria-label="Precio mínimo" name="precio_min" value="<?= htmlspecialchars($precio_min) ?>">
                            <input class="form-control" style="max-width: 120px;" type="number" placeholder="Máx" aria-label="Precio máximo" name="precio_max" value="<?= htmlspecialchars($precio_max) ?>">
                            <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i></button>
                        </div>
                    </form>
                    <button class="btn btn-outline-primary position-relative" id="carritoBtn" data-bs-toggle="modal" data-bs-target="#carritoModal">
                        <i class="bi bi-cart3"></i> Carrito
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            <?= array_reduce($_SESSION['carrito'], function($total, $item) { return $total + $item['cantidad']; }, 0) ?>
                        </span>
                    </button>
                </div>
            </div>
        </nav>

        <!-- Listado de coches -->
        <div class="container py-4">
            <h3 class="mb-4 text-primary">Nuestros Coches</h3>
            
            <?php if (isset($mensaje)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= $mensaje ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <div class="row row-cols-1 row-cols-md-3 g-4">
                <?php while($coche = $coches->fetch_assoc()): ?>
                    <div class="col">
                        <div class="card h-100 shadow-sm">
                            <img src="img/<?= htmlspecialchars($coche['imagen']) ?>" class="card-img-top" alt="<?= htmlspecialchars($coche['modelo']) ?>">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($coche['modelo']) ?></h5>
                                <p class="card-text text-muted"><?= htmlspecialchars($coche['marca']) ?></p>
                                <p class="card-text fw-bold text-primary"><?= number_format($coche['precio'], 0, ',', '.') ?> €</p>
                                <form method="POST" class="d-flex">
                                    <input type="hidden" name="accion" value="agregar">
                                    <input type="hidden" name="coche_id" value="<?= $coche['id'] ?>">
                                    <input type="number" name="cantidad" value="1" min="1" class="form-control me-2" style="width: 70px;">
                                    <button type="submit" class="btn btn-success flex-grow-1">
                                        <i class="bi bi-cart-plus"></i> Añadir
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <hr>
            <!-- Paginación -->
            <div class="mt-4 text-center">
                <a href="coches2.php" class="btn btn-primary">Página Siguiente</a>
            </div>
        </div>
    </div>
</div>

<!-- Modal del carrito -->
<div class="modal fade" id="carritoModal" tabindex="-1" aria-labelledby="carritoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="carritoModalLabel"><i class="bi bi-cart3 me-2"></i>Tu Carrito</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <?php if (empty($_SESSION['carrito'])): ?>
                    <div class="text-center py-4">
                        <i class="bi bi-cart-x" style="font-size: 3rem; color: #ccc;"></i>
                        <h5 class="mt-3">Tu carrito está vacío</h5>
                        <p class="text-muted">Añade algunos coches para comenzar</p>
                    </div>
                <?php else: ?>
                    <form method="POST" id="formCarrito">
                        <input type="hidden" name="accion" value="actualizar">
                        <ul class="list-group">
                            <?php 
                            $total = 0;
                            foreach ($_SESSION['carrito'] as $item): 
                                $subtotal = $item['precio'] * $item['cantidad'];
                                $total += $subtotal;
                            ?>
                                <li class="list-group-item carrito-item">
                                    <div class="row align-items-center">
                                        <div class="col-md-2">
                                            <img src="img/<?= htmlspecialchars($item['imagen']) ?>" class="img-fluid rounded" alt="<?= htmlspecialchars($item['modelo']) ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <h6 class="mb-1"><?= htmlspecialchars($item['modelo']) ?></h6>
                                            <small class="text-muted"><?= number_format($item['precio'], 0, ',', '.') ?> €</small>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="input-group">
                                                <input type="number" name="cantidad[<?= $item['id'] ?>]" value="<?= $item['cantidad'] ?>" min="1" class="form-control">
                                                <button class="btn btn-outline-secondary" type="submit">
                                                    <i class="bi bi-arrow-clockwise"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="col-md-2 text-end">
                                            <span class="fw-bold"><?= number_format($subtotal, 0, ',', '.') ?> €</span>
                                        </div>
                                        <div class="col-md-1 text-end">
                                            <form method="POST">
                                                <input type="hidden" name="accion" value="eliminar">
                                                <input type="hidden" name="coche_id" value="<?= $item['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <div class="d-flex justify-content-between align-items-center mt-3 p-3 bg-light rounded">
                            <h5 class="mb-0">Total:</h5>
                            <h4 class="mb-0 carrito-total"><?= number_format($total, 0, ',', '.') ?> €</h4>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Seguir Comprando</button>
                <?php if (!empty($_SESSION['carrito'])): ?>
                    <form method="POST">
                        <input type="hidden" name="accion" value="comprar">
                        <button type="submit" class="btn btn-primary">Finalizar Compra</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Mostrar alertas
function mostrarAlerta(mensaje, tipo) {
    const alerta = document.createElement('div');
    alerta.className = `alert alert-${tipo} alert-fixed`;
    alerta.innerHTML = `
        ${mensaje}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    document.body.appendChild(alerta);
    
    setTimeout(() => {
        alerta.remove();
    }, 3000);
}

// Manejar envío del formulario del carrito
document.getElementById('formCarrito')?.addEventListener('submit', function(e) {
    e.preventDefault();
    this.submit();
});

// Actualizar el badge del carrito cuando se cierra el modal
const carritoModal = document.getElementById('carritoModal');
if (carritoModal) {
    carritoModal.addEventListener('hidden.bs.modal', function() {
        // Recargar la página para actualizar los datos
        location.reload();
    });
}
</script>
</body>
</html>