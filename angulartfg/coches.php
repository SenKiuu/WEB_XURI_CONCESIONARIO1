<?php
session_start();
include("includes/conexion.php");

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

// Procesar compra si se envió el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['finalizar_compra'])) {
    if (!empty($_SESSION['carrito'])) {
        $usuario_id = $_SESSION['usuario']['id'];
        $errores = [];
        
        $conn->begin_transaction();
        
        try {
            foreach ($_SESSION['carrito'] as $item) {
                for ($i = 0; $i < $item['cantidad']; $i++) {
                    $stmt = $conn->prepare("INSERT INTO compras (usuario_id, coche_id) VALUES (?, ?)");
                    $stmt->bind_param("ii", $usuario_id, $item['id']);
                    
                    if (!$stmt->execute()) {
                        throw new Exception("Error al registrar la compra: " . $stmt->error);
                    }
                    
                    $stmt->close();
                }
            }
            
            $conn->commit();
            $_SESSION['carrito'] = []; // Vaciar el carrito
            $mensaje_exito = "Compra realizada con éxito";
        } catch (Exception $e) {
            $conn->rollback();
            $errores[] = "Error al procesar la compra: " . $e->getMessage();
        }
    }
}

// Procesar acciones del carrito
if (isset($_GET['accion_carrito'])) {
    switch ($_GET['accion_carrito']) {
        case 'agregar':
            if (isset($_GET['id'])) {
                $id = intval($_GET['id']);
                $sql = "SELECT id, modelo, precio FROM coches WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $coche = $result->fetch_assoc();
                    
                    if (!isset($_SESSION['carrito'])) {
                        $_SESSION['carrito'] = [];
                    }
                    
                    $encontrado = false;
                    foreach ($_SESSION['carrito'] as &$item) {
                        if ($item['id'] === $coche['id']) {
                            $item['cantidad'] += 1;
                            $encontrado = true;
                            break;
                        }
                    }
                    
                    if (!$encontrado) {
                        $_SESSION['carrito'][] = [
                            'id' => $coche['id'],
                            'modelo' => $coche['modelo'],
                            'precio' => $coche['precio'],
                            'cantidad' => 1
                        ];
                    }
                    
                    $mensaje_exito = $coche['modelo'] . " añadido al carrito";
                }
            }
            break;
            
        case 'eliminar':
            if (isset($_GET['id']) && isset($_SESSION['carrito'])) {
                $id = intval($_GET['id']);
                $_SESSION['carrito'] = array_filter($_SESSION['carrito'], function($item) use ($id) {
                    return $item['id'] !== $id;
                });
            }
            break;
            
        case 'vaciar':
            unset($_SESSION['carrito']);
            break;
    }
    
    // Redirigir para evitar reenvío del formulario
    header("Location: coches.php");
    exit();
}

// Obtener parámetros de búsqueda
$busqueda = isset($_GET['busqueda']) ? $_GET['busqueda'] : '';
$precio_min = isset($_GET['precio_min']) ? floatval($_GET['precio_min']) : '';
$precio_max = isset($_GET['precio_max']) ? floatval($_GET['precio_max']) : '';

// Configuración de paginación
$por_pagina = 12;
$pagina_actual = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$offset = ($pagina_actual - 1) * $por_pagina;

// Consulta base
$sql = "SELECT c.*, cd.marca, cd.ano, cd.combustible, cd.potencia 
        FROM coches c
        LEFT JOIN coche_detalles cd ON c.id = cd.coche_id
        WHERE 1";

// Aplicar filtros
if (!empty($busqueda)) {
    $sql .= " AND c.modelo LIKE '%" . $conn->real_escape_string($busqueda) . "%'";
}

if (!empty($precio_min) && !empty($precio_max)) {
    $sql .= " AND c.precio BETWEEN $precio_min AND $precio_max";
} elseif (!empty($precio_min)) {
    $sql .= " AND c.precio >= $precio_min";
} elseif (!empty($precio_max)) {
    $sql .= " AND c.precio <= $precio_max";
}

// Consulta para el total
$resultado_total = $conn->query(str_replace('c.*, cd.marca, cd.ano, cd.combustible, cd.potencia', 'COUNT(*) as total', $sql));
$total_coches = $resultado_total->fetch_assoc()['total'];
$total_paginas = ceil($total_coches / $por_pagina);

// Consulta paginada
$sql .= " LIMIT $por_pagina OFFSET $offset";
$resultado = $conn->query($sql);

if (!$resultado) {
    die("Error en la consulta: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Concesionario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .card-img-top {
            height: 180px;
            object-fit: cover;
            cursor: pointer;
        }
        .card {
            transition: transform 0.3s;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        #itemsCarrito {
            max-height: 400px;
            overflow-y: auto;
        }
        .alert-fixed {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1100;
            min-width: 300px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        .btn-carrito {
            transition: all 0.2s;
        }
        .btn-carrito:hover {
            transform: scale(1.05);
        }
    </style>
</head>
<body class="bg-light">
<div class="d-flex">
    <!-- Sidebar -->
    <div class="bg-dark text-white p-4" style="width: 250px; min-height: 100vh;">
        <div class="text-center mb-4">
            <img src="./img/logo.jpg" class="rounded-circle" width="100" alt="Logo">
            <h5 class="mt-2">Concesionario</h5>
        </div>
        <hr>
        <nav class="nav flex-column">
            <a class="nav-link text-white active" href="coches.php"><i class="bi bi-house-door"></i> Inicio</a>
            <a class="nav-link text-white" href="perfil.php"><i class="bi bi-person"></i> Perfil</a>
            <a class="nav-link text-white" href="logout.php"><i class="bi bi-box-arrow-right"></i> Cerrar sesión</a>
        </nav>
    </div>

    <!-- Contenido principal -->
    <div class="flex-fill">
        <!-- Navbar -->
        <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
            <div class="container-fluid">
                <span class="navbar-brand">Bienvenido, <?= htmlspecialchars($_SESSION['usuario']['nombre']) ?></span>
                <div class="d-flex">
                    <form class="d-flex me-2" method="GET" action="coches.php">
                        <input type="hidden" name="pagina" value="1">
                        <input class="form-control me-2" type="search" placeholder="Buscar" name="busqueda" value="<?= htmlspecialchars($busqueda) ?>">
                        <button class="btn btn-outline-primary" type="submit"><i class="bi bi-search"></i></button>
                    </form>
                    <button class="btn btn-primary position-relative" id="btnCarrito" data-bs-toggle="modal" data-bs-target="#modalCarrito">
                        <i class="bi bi-cart"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            <?= isset($_SESSION['carrito']) ? array_reduce($_SESSION['carrito'], function($sum, $item) { return $sum + $item['cantidad']; }, 0) : 0 ?>
                        </span>
                    </button>
                </div>
            </div>
        </nav>

        <!-- Mostrar mensajes -->
        <?php if (isset($mensaje_exito)): ?>
            <div class="alert alert-success alert-dismissible fade show alert-fixed" role="alert">
                <div class="d-flex align-items-center">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <span><?= htmlspecialchars($mensaje_exito) ?></span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($errores)): ?>
            <?php foreach ($errores as $error): ?>
                <div class="alert alert-danger alert-dismissible fade show alert-fixed" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <span><?= htmlspecialchars($error) ?></span>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Filtros -->
        <div class="container mt-3">
            <form method="GET" action="coches.php">
                <input type="hidden" name="pagina" value="1">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label>Precio mínimo</label>
                        <input type="number" class="form-control" name="precio_min" value="<?= htmlspecialchars($precio_min) ?>">
                    </div>
                    <div class="col-md-4">
                        <label>Precio máximo</label>
                        <input type="number" class="form-control" name="precio_max" value="<?= htmlspecialchars($precio_max) ?>">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Listado de coches -->
        <div class="container py-4">
            <h2 class="mb-4">Nuestros Coches</h2>
            
            <?php if ($resultado->num_rows > 0): ?>
                <div class="row row-cols-1 row-cols-md-3 g-4">
                    <?php while($coche = $resultado->fetch_assoc()): ?>
                        <div class="col">
                            <div class="card h-100 shadow-sm">
                                <img src="img/<?= htmlspecialchars($coche['imagen']) ?>" class="card-img-top" alt="<?= htmlspecialchars($coche['modelo']) ?>" onclick="verDetalle(<?= $coche['id'] ?>)">
                                <div class="card-body">
                                    <h5 class="card-title"><?= htmlspecialchars($coche['modelo']) ?></h5>
                                    <p class="card-text">
                                        <span class="badge bg-primary"><?= htmlspecialchars($coche['marca']) ?></span>
                                        <span class="badge bg-secondary"><?= htmlspecialchars($coche['ano']) ?></span>
                                        <span class="badge bg-success"><?= number_format($coche['precio'], 2) ?> €</span>
                                    </p>
                                </div>
                                <div class="card-footer bg-white">
                                    <a href="?accion_carrito=agregar&id=<?= $coche['id'] ?>" class="btn btn-sm btn-success btn-carrito">
                                        <i class="bi bi-cart-plus"></i> Añadir
                                    </a>
                                    <button class="btn btn-sm btn-primary float-end" onclick="verDetalle(<?= $coche['id'] ?>)">
                                        <i class="bi bi-info-circle"></i> Detalles
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-warning">No se encontraron coches con los filtros aplicados.</div>
            <?php endif; ?>

            <!-- Paginación -->
            <?php if ($total_paginas > 1): ?>
                <nav class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($pagina_actual > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina' => $pagina_actual - 1])) ?>">Anterior</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                            <li class="page-item <?= $i == $pagina_actual ? 'active' : '' ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina' => $i])) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($pagina_actual < $total_paginas): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina' => $pagina_actual + 1])) ?>">Siguiente</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal de detalles -->
<div class="modal fade" id="modalDetalle" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalles del Coche</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="detalleCoche">
                <!-- Contenido cargado por AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" id="btnAddFromModal">Añadir al carrito</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal del carrito -->
<div class="modal fade" id="modalCarrito" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tu Carrito</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php if (empty($_SESSION['carrito'])): ?>
                    <div class="alert alert-info">El carrito está vacío</div>
                <?php else: ?>
                    <div id="itemsCarrito">
                        <?php 
                        $total = 0;
                        foreach ($_SESSION['carrito'] as $item): 
                            $subtotal = $item['precio'] * $item['cantidad'];
                            $total += $subtotal;
                        ?>
                            <div class="card mb-2">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6><?= htmlspecialchars($item['modelo']) ?></h6>
                                            <small class="text-muted">Precio unitario: <?= number_format($item['precio'], 2) ?> €</small>
                                        </div>
                                        <span class="badge bg-primary rounded-pill"><?= $item['cantidad'] ?> unidad(es)</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mt-2">
                                        <strong>Subtotal: <?= number_format($subtotal, 2) ?> €</strong>
                                        <a href="?accion_carrito=eliminar&id=<?= $item['id'] ?>" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash"></i> Eliminar
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="d-flex justify-content-between mt-3">
                        <h5>Total:</h5>
                        <h5 id="totalCarrito"><?= number_format($total, 2) ?> €</h5>
                    </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <?php if (!empty($_SESSION['carrito'])): ?>
                    <a href="?accion_carrito=vaciar" class="btn btn-outline-danger me-auto">
                        <i class="bi bi-trash"></i> Vaciar carrito
                    </a>
                <?php endif; ?>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Seguir comprando</button>
                <form method="POST" style="display: inline;">
                    <button type="submit" name="finalizar_compra" class="btn btn-success" <?= empty($_SESSION['carrito']) ? 'disabled' : '' ?>>
                        Finalizar compra
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
// Ver detalles del coche
function verDetalle(cocheId) {
    $.ajax({
        url: 'api/detalle_coche.php',
        method: 'GET',
        data: { id: cocheId },
        success: function(data) {
            $('#detalleCoche').html(data);
            const modal = new bootstrap.Modal(document.getElementById('modalDetalle'));
            modal.show();
            
            // Configurar botón de añadir desde el modal
            $('#btnAddFromModal').off('click').on('click', function() {
                window.location.href = `?accion_carrito=agregar&id=${cocheId}`;
                modal.hide();
            });
        },
        error: function() {
            alert('Error al cargar detalles');
        }
    });
}

// Cerrar automáticamente las alertas después de 3 segundos
$(document).ready(function() {
    setTimeout(function() {
        $('.alert-fixed').alert('close');
    }, 3000);
});
</script>
</body>
</html>