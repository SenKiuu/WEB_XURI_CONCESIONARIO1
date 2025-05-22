<?php
session_start();
include("includes/conexion.php");

if (!isset($_SESSION["usuario"]) || $_SESSION["usuario"]["rol"] !== "admin") {
    header("Location: index.php");
    exit();
}

// Obtener todos los usuarios
$sql = "SELECT id, nombre, email, password, rol FROM usuarios";
$resultado = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Pro | Panel de Control</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --secondary: #f43f5e;
            --success: #10b981;
            --warning: #f59e0b;
            --dark: #1e293b;
            --light: #f8fafc;
            --gray: #94a3b8;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f1f5f9;
            color: var(--dark);
            overflow-x: hidden;
        }
        
        .glass-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: 16px;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
        }
        
        .glass-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }
        
        .admin-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border-radius: 16px 16px 0 0;
            color: white;
            padding: 1.5rem;
        }
        
        .table-admin {
            --bs-table-bg: transparent;
            --bs-table-striped-bg: rgba(241, 245, 249, 0.5);
            margin-bottom: 0;
        }
        
        .table-admin th {
            background-color: rgba(241, 245, 249, 0.9);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            color: var(--gray);
            border-bottom-width: 2px;
        }
        
        .table-admin td {
            vertical-align: middle;
            padding: 1rem;
            border-color: rgba(226, 232, 240, 0.5);
        }
        
        .table-admin tr:hover td {
            background-color: rgba(236, 239, 253, 0.7);
        }
        
        .badge-admin {
            padding: 0.35rem 0.65rem;
            font-weight: 500;
            letter-spacing: 0.5px;
            font-size: 0.7rem;
            border-radius: 50px;
        }
        
        .btn-admin {
            border-radius: 50px;
            padding: 0.5rem 1rem;
            font-weight: 500;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
        }
        
        .btn-admin i {
            font-size: 1rem;
        }
        
        .btn-admin-view {
            background-color: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }
        
        .btn-admin-view:hover {
            background-color: var(--success);
            color: white;
        }
        
        .btn-admin-edit {
            background-color: rgba(245, 158, 11, 0.1);
            color: var(--warning);
        }
        
        .btn-admin-edit:hover {
            background-color: var(--warning);
            color: white;
        }
        
        .btn-admin-delete {
            background-color: rgba(244, 63, 94, 0.1);
            color: var(--secondary);
        }
        
        .btn-admin-delete:hover {
            background-color: var(--secondary);
            color: white;
        }
        
        .logout-btn {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            border-radius: 50px;
            padding: 0.5rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
        }
        
        .logout-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }
        
        .admin-title {
            position: relative;
            display: inline-block;
            font-weight: 700;
            color: white;
        }
        
        .admin-title:after {
            content: '';
            position: absolute;
            width: 40%;
            height: 3px;
            background: rgba(255, 255, 255, 0.5);
            bottom: -8px;
            left: 0;
            border-radius: 3px;
        }
        
        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: var(--primary);
            color: white;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-right: 0.5rem;
        }
        
        .password-display {
            font-family: 'Courier New', monospace;
            background: rgba(226, 232, 240, 0.5);
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.85rem;
        }
        
        .stats-card {
            border-radius: 12px;
            padding: 1.5rem;
            color: white;
            margin-bottom: 1.5rem;
        }
        
        .stats-card i {
            font-size: 2rem;
            opacity: 0.8;
        }
        
        .stats-card .count {
            font-size: 2rem;
            font-weight: 700;
            margin: 0.5rem 0;
        }
        
        .stats-card .label {
            font-size: 0.85rem;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <!-- Header Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="glass-card p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="h4 mb-0 fw-bold text-primary">
                                <i class="bi bi-speedometer2 me-2"></i>Panel de Administración
                            </h1>
                            <p class="mb-0 text-muted">Bienvenido, <?= htmlspecialchars($_SESSION['usuario']['nombre']) ?></p>
                        </div>
                        <div>
                            <a href="logout.php" class="logout-btn">
                                <i class="bi bi-box-arrow-right me-2"></i>Cerrar sesión
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Stats Cards -->
         <?php
        // Consulta para contar el número total de administradores
        $sql_admins = "SELECT COUNT(*) as total_admins FROM usuarios WHERE rol = 'admin'";
        $resultado_admins = $conn->query($sql_admins);
        $total_admins = $resultado_admins->fetch_assoc()['total_admins'];

        // Consulta para contar el número total de usuarios normales
        $sql_usuarios = "SELECT COUNT(*) as total_usuarios FROM usuarios WHERE rol = 'usuario'"; 
        $resultado_usuarios = $conn->query($sql_usuarios);
        $total_usuarios = $resultado_usuarios->fetch_assoc()['total_usuarios'];

        // El total de usuarios ya lo tenemos en $resultado->num_rows
        $total_usuarios_registrados = $resultado->num_rows;
        ?>

        <!-- Stats Cards con datos reales -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="stats-card" style="background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);">
                    <i class="bi bi-people-fill"></i>
                    <div class="count"><?= $total_usuarios_registrados ?></div>
                    <div class="label">Usuarios Registrados</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card" style="background: linear-gradient(135deg, #f97316 0%, #f59e0b 100%);">
                    <i class="bi bi-shield-lock"></i>
                    <div class="count"><?= $total_admins ?></div>
                    <div class="label">Administradores</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card" style="background: linear-gradient(135deg, #10b981 0%, #34d399 100%);">
                    <i class="bi bi-person-check"></i>
                    <div class="count"><?= $total_usuarios ?></div>
                    <div class="label">Usuarios Normales</div>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="row">
            <div class="col-12">
                <div class="glass-card overflow-hidden">
                    <div class="admin-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="admin-title mb-0">
                                <i class="bi bi-table me-2"></i>Gestión de Usuarios
                            </h5>
                            <div>
                                <button class="btn btn-sm btn-light">
                                    <i class="bi bi-plus-circle me-1"></i> Nuevo Usuario
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-admin table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Usuario</th>
                                    <th>Contacto</th>
                                    <th>Seguridad</th>
                                    <th>Rol</th>
                                    <th class="text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($usuario = $resultado->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="user-avatar">
                                                    <?= strtoupper(substr($usuario['nombre'], 0, 1)) ?>
                                                </div>
                                                <div>
                                                    <div class="fw-semibold"><?= htmlspecialchars($usuario['nombre']) ?></div>
                                                    <small class="text-muted">ID: <?= $usuario['id'] ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div><?= htmlspecialchars($usuario['email']) ?></div>
                                            <small class="text-muted">Últ. acceso: <?= date('d/m/Y') ?></small>
                                        </td>
                                        <td>
                                            <span class="password-display">
                                                <?= htmlspecialchars(substr($usuario['password'], 0, 2)) . str_repeat('*', 12) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-admin bg-<?= $usuario['rol'] === 'admin' ? 'primary' : 'success' ?>">
                                                <?= htmlspecialchars($usuario['rol']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex justify-content-end">
                                                <a href="ver_usuario.php?id=<?= $usuario['id'] ?>" 
                                                   class="btn btn-admin btn-admin-view me-2"
                                                   data-bs-toggle="tooltip" 
                                                   data-bs-placement="top" 
                                                   title="Ver detalles">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="modificar_usuario.php?id=<?= $usuario['id'] ?>" 
                                                   class="btn btn-admin btn-admin-edit me-2"
                                                   data-bs-toggle="tooltip" 
                                                   data-bs-placement="top" 
                                                   title="Editar usuario">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="eliminar_usuario.php?id=<?= $usuario['id'] ?>" 
                                                   class="btn btn-admin btn-admin-delete"
                                                   data-bs-toggle="tooltip" 
                                                   data-bs-placement="top" 
                                                   title="Eliminar usuario"
                                                   onclick="return confirm('¿Estás seguro de eliminar este usuario?')">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    
                   
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Activar tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Efecto de carga
        document.addEventListener('DOMContentLoaded', function() {
            const rows = document.querySelectorAll('tbody tr');
            rows.forEach((row, index) => {
                row.style.opacity = '0';
                row.style.transform = 'translateY(20px)';
                row.style.transition = `all 0.3s ease ${index * 0.05}s`;
                
                setTimeout(() => {
                    row.style.opacity = '1';
                    row.style.transform = 'translateY(0)';
                }, 100);
            });
        });
    </script>
</body>
</html>