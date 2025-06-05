        <?php
        $servername = "www.thyagoquintas.com.br:3306";
        $username = "engenharia_29";
        $password = "graxaimdomato";
        $dbname = "engenharia_29";

        $conn = new mysqli($servername, $username, $password, $dbname);
        if ($conn->connect_error) {
            die("Falha na conexão: " . $conn->connect_error);
        }

        // === INSERÇÃO DE DADOS (se algum parâmetro GET for enviado) ===
        $temperatura = isset($_GET['temperatura']) ? floatval($_GET['temperatura']) : null;
        $pressao = isset($_GET['pressao']) ? floatval($_GET['pressao']) : null;
        $aprox_alt = isset($_GET['aprox_alt']) ? floatval($_GET['aprox_alt']) : null;
        $velocidade = isset($_GET['velocidade']) ? floatval($_GET['velocidade']) : null;
        $latitude = isset($_GET['latitude']) ? floatval($_GET['latitude']) : null;
        $longitude = isset($_GET['longitude']) ? floatval($_GET['longitude']) : null;

        if (
            $temperatura !== null || $pressao !== null || $aprox_alt !== null ||
            $velocidade !== null || $latitude !== null || $longitude !== null
        ) {
            $query = "INSERT INTO DADOS_SENSOR (";
            $values = "VALUES (";
            $types = "";
            $params = [];

            if ($velocidade !== null) {
                $query .= "velocidade, ";
                $values .= "?, ";
                $types .= "d";
                $params[] = $velocidade;
            }
            if ($temperatura !== null) {
                $query .= "temperatura, ";
                $values .= "?, ";
                $types .= "d";
                $params[] = $temperatura;
            }
            if ($pressao !== null) {
                $query .= "pressao, ";
                $values .= "?, ";
                $types .= "d";
                $params[] = $pressao;
            }
            if ($aprox_alt !== null) {
                $query .= "aprox_alt, ";
                $values .= "?, ";
                $types .= "d";
                $params[] = $aprox_alt;
            }
            if ($longitude !== null) {
                $query .= "longitude, ";
                $values .= "?, ";
                $types .= "d";
                $params[] = $longitude;
            }
            if ($latitude !== null) {
                $query .= "latitude, ";
                $values .= "?, ";
                $types .= "d";
                $params[] = $latitude;
            }

            $query = rtrim($query, ", ") . ")";
            $values = rtrim($values, ", ") . ")";
            $sql = $query . " " . $values;

            $stmt = $conn->prepare($sql);
            if ($params) {
                $stmt->bind_param($types, ...$params);
            }

            if ($stmt->execute()) {
                echo "<p style='color:green;'>Dados inseridos com sucesso!</p>";
            } else {
                echo "<p style='color:red;'>Erro ao inserir dados: " . $stmt->error . "</p>";
            }

            $stmt->close();
        }
        // === FIM DA INSERÇÃO ===

        // Consulta os dados para exibir na tabela e gráficos
        $sql = "SELECT * FROM DADOS_SENSOR ORDER BY hora DESC";
        $result = $conn->query($sql);
        ?>

        <!DOCTYPE html>
        <html lang="pt-br">
        <head>
          <meta charset="UTF-8" />
          <title>Leituras do Sensor</title>
          <link rel="stylesheet" href="estilo.css" />
          <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
          <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDdxDjbY05YF26mA98qNE-l3_v4M0WjYuM&callback=initMap" async defer></script>
        </head>
        <body>

        <header>
          <div class="container">
            <h1>Painel de Monitoramento</h1>
            <p>Leituras dos Sensores</p>
          </div>
        </header>
<br><br>
        <main>
          <section class="tabela-container">
            <table>
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Hora</th>
                  <th>Temperatura</th>
                  <th>Latitude</th>
                  <th>Longitude</th>
                  <th>Altitude</th>
                  <th>Pressão</th>
                  <th>Mapa</th>
                </tr>
              </thead>
              <tbody>
                <?php
                  $horas = [];
                  $temperaturas = [];
                  $velocidades = [];
                  $latitudes = [];
                  $longitudes = [];
                  $pressoes = [];

                  if ($result->num_rows > 0) {
                      while ($row = $result->fetch_assoc()) {
                          echo "<tr>
                                  <td>{$row['id_dado']}</td>
                                  <td>{$row['hora']}</td>
                                  <td>{$row['temperatura']}</td>
                                  <td>{$row['latitude']}</td>
                                  <td>{$row['longitude']}</td>
                                  <td>{$row['aprox_alt']}</td>
                                  <td>{$row['pressao']}</td>
                                  <td><a href='https://www.google.com/maps?q={$row['latitude']},{$row['longitude']}' target='_blank'>Google Maps</a></td>
                                </tr>";
                          $horas[] = $row['hora'];
                          $temperaturas[] = $row['temperatura'];
                          $velocidades[] = $row['velocidade'];
                          $latitudes[] = $row['latitude'];
                          $longitudes[] = $row['longitude'];
                          $pressoes[] = $row['pressao'];
                      }
                  } else {
                      echo "<tr><td colspan='8'>Nenhum dado encontrado</td></tr>";
                  }
                  $intervaloTexto = "";
                  if (count($horas) >= 2) {
                      $dataInicio = date('d/m', strtotime(end($horas)));
                      $dataFim = date('d/m', strtotime($horas[0]));    
                      $intervaloTexto = "do dia $dataInicio ao $dataFim";
                  } elseif (count($horas) == 1) {
                      $dataUnica = date('d/m', strtotime($horas[0]));
                      $intervaloTexto = "no dia $dataUnica";
                  } else {
                      $intervaloTexto = "Sem dados";
                  }

                ?>
              </tbody>
            </table>
          </section>

          <section id="grafico">
            <canvas id="sensorChart"></canvas>
          </section>

          <section id="mapa" style="width: 100%; height: 400px;"></section>
        </main>

        <footer>
          <div class="footer-content"></div>
        </footer>

        <script>
          const horas = <?php echo json_encode($horas); ?>;
          const temperaturas = <?php echo json_encode($temperaturas); ?>;
          const velocidades = <?php echo json_encode($velocidades); ?>;
          const pressoes = <?php echo json_encode($pressoes); ?>;
          const latitudes = <?php echo json_encode($latitudes); ?>;
          const longitudes = <?php echo json_encode($longitudes); ?>;
          const intervaloTexto = <?php echo json_encode($intervaloTexto); ?>;
            
            const ctx = document.getElementById('sensorChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: horas,
                    datasets: [
                        {
                            label: 'Temperatura (°C)',
                            data: temperaturas,
                            borderColor: 'rgba(255, 99, 132, 1)',
                            backgroundColor: 'rgba(255, 99, 132, 0.2)',
                            yAxisID: 'y',
                            fill: true,
                            tension: 0.3
                        },
                        {
                            label: 'Pressão (atm)',
                            data: pressoes,
                            borderColor: 'rgba(54, 162, 235, 1)',
                            backgroundColor: 'rgba(54, 162, 235, 0.2)',
                            yAxisID: 'y1',
                            fill: true,
                            tension: 0.3
                        }
                    ]
                },
                options: {
                    responsive: true,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    stacked: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Temperatura x Pressão ao Longo do Tempo'
                        },
                        legend: {
                            position: 'top'
                        }
                    },
                    scales: {
                        x: {
                            title: {
                                display: true,
                                text: 'Hora'
                            }
                        },
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: 'Temperatura (°C)'
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            grid: {
                                drawOnChartArea: false
                            },
                            title: {
                                display: true,
                                text: 'Pressão (atm)'
                            }
                        }
                    }
                }
            });

          function initMap() {
            if (latitudes.length === 0 || longitudes.length === 0) return;

            const map = new google.maps.Map(document.getElementById('mapa'), {
              zoom: 10,
              center: { lat: parseFloat(latitudes[0]), lng: parseFloat(longitudes[0]) }
            });

            for (let i = 0; i < latitudes.length; i++) {
              new google.maps.Marker({
                position: { lat: parseFloat(latitudes[i]), lng: parseFloat(longitudes[i]) },
                map: map,
                title: `Sensor ID: ${i + 1}`
              });
            }
          }
        </script>

        </body>
        </html>

        <?php $conn->close(); ?>
