import moment from 'moment';
import {
  Chart,
  ArcElement,
  LineElement,
  BarElement,
  PointElement,
  BarController,
  BubbleController,
  DoughnutController,
  LineController,
  PieController,
  PolarAreaController,
  RadarController,
  ScatterController,
  CategoryScale,
  LinearScale,
  LogarithmicScale,
  RadialLinearScale,
  TimeScale,
  TimeSeriesScale,
  Decimation,
  Filler,
  Legend,
  Title,
  Tooltip,
  SubTitle,
} from 'chart.js';

Chart.register(
  ArcElement,
  LineElement,
  BarElement,
  PointElement,
  BarController,
  BubbleController,
  DoughnutController,
  LineController,
  PieController,
  PolarAreaController,
  RadarController,
  ScatterController,
  CategoryScale,
  LinearScale,
  LogarithmicScale,
  RadialLinearScale,
  TimeScale,
  TimeSeriesScale,
  Decimation,
  Filler,
  Legend,
  Title,
  Tooltip,
  SubTitle
);

$(function () {
  const $form = $('#searchTrend2PageForm');
  if (!$form.length) {
    return;
  }

  const urls = {
    search: $form.data('searchUrl'),
    incidents: $form.data('incidentsUrl'),
    targets: $form.data('targetsUrl'),
  };

  let resultChart = null;

  function generateGraph() {
    const data = {
      start: $('#start').val(),
      end: $('#end').val(),
      type: $('#type').val(),
      region: $('#region').val(),
    };

    if (!data.start || !data.type || !data.region || data.region.length === 0) {
      alert('Veuillez renseigner la période, l\'indicateur et au moins une région.');
      return;
    }

    if (!urls.search) {
      alert('URL de génération introuvable.');
      return;
    }

    $.ajax({
      method: 'POST',
      url: urls.search,
      data: data,
    }).done(function (response) {
      if (response.error) {
        alert(response.error);
        return;
      }

      const backgroundColor = [
        'rgba(54, 162, 235, 0.6)',
        'rgba(255, 206, 86, 0.6)',
        'rgba(75, 192, 192, 0.6)',
        'rgba(153, 102, 255, 0.6)',
        'rgba(255, 159, 64, 0.6)',
        'rgba(255, 99, 132, 0.6)',
      ];

      const datasets = [];
      for (let index = 0; index < response.countMonth.length; index++) {
        datasets.push({
          label: response.countMonth[index].label,
          data: response.countMonth[index].donnees,
          backgroundColor: backgroundColor[index % backgroundColor.length],
          borderColor: backgroundColor[index % backgroundColor.length],
          borderWidth: 1,
        });
      }

      $('.resultat').html('<canvas id="resultatGraph"></canvas>');

      if (resultChart) {
        resultChart.destroy();
      }

      const canvas = document.getElementById('resultatGraph');
      if (!canvas) {
        return;
      }

      resultChart = new Chart(canvas.getContext('2d'), {
        type: 'bar',
        data: {
          labels: response.regions,
          datasets: datasets,
        },
        options: {
          responsive: true,
          scales: {
            y: {
              beginAtZero: true,
            },
          },
        },
      });
    }).fail(function (xhr) {
      let message = 'Impossible de générer le graphique.';
      if (xhr.responseJSON && xhr.responseJSON.error) {
        message = xhr.responseJSON.error;
      }
      alert(message);
    });
  }

  $('#generateGraphBtn').on('click', generateGraph);
  $form.on('submit', function (e) {
    e.preventDefault();
    generateGraph();
  });

  $('.stopSearchTrend2PageForm').on('click', function () {
    if (resultChart) {
      resultChart.destroy();
      resultChart = null;
    }
    $('.resultat').empty();
    $('#start').val('');
    $('#end').val('');
    $('#type').val($('#type option:first').val()).trigger('change');
    $('#region').val(null).trigger('change');
  });

  moment.locale('fr');

  if (urls.incidents && document.getElementById('nbTerroristIncidents')) {
    $.get(urls.incidents).done(function (data) {
      displayNbTerroristIncidents(data.totalAttack, data.totalDeath, data.totalInjured);
    });
  }

  if (urls.targets && document.getElementById('prTargetsOfAttacks')) {
    $.get(urls.targets).done(function (data) {
      displayPrTargetsOfAttacks(data.totalCivil, data.totalSecuriteMilitaire, data.totalTerroriste);
    });
  }
});

function displayNbTerroristIncidents(totalAttack, totalDeath, totalInjured) {
  const canvas = document.getElementById('nbTerroristIncidents');
  if (!canvas) {
    return;
  }

  new Chart(canvas, {
    type: 'bar',
    data: {
      labels: ['Attaques', 'Décès', 'Blessés'],
      datasets: [{
        label: 'Incidents terroristes',
        data: [totalAttack, totalDeath, totalInjured],
        backgroundColor: [
          'rgba(255, 99, 132, 0.5)',
          'rgba(54, 162, 235, 0.5)',
          'rgba(255, 206, 86, 0.5)',
        ],
        borderWidth: 1,
      }],
    },
    options: {
      scales: {
        y: {
          beginAtZero: true,
        },
      },
    },
  });
}

function displayPrTargetsOfAttacks(totalCivil, totalSecuriteMilitaire, totalTerroriste) {
  const canvas = document.getElementById('prTargetsOfAttacks');
  if (!canvas) {
    return;
  }

  new Chart(canvas, {
    type: 'pie',
    data: {
      labels: ['Civils', 'Sécurité / militaire', 'Terroristes'],
      datasets: [{
        label: 'Cibles des attaques',
        data: [totalCivil, totalSecuriteMilitaire, totalTerroriste],
        backgroundColor: [
          'rgba(255, 99, 132, 0.5)',
          'rgba(54, 162, 235, 0.5)',
          'rgba(255, 206, 86, 0.5)',
        ],
        borderWidth: 1,
      }],
    },
  });
}
