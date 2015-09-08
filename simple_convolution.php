<?php
/*
 * a php function that is as close as possible to the simple convolution
 *   routine found in the DWR RGDSS response function workbooks
 * the routine is not actually used in the final RGDSS calculations,
 *   but it is a good building block to start with, and later could come in handy
 * 
 * the VBA code from the DWR workbook
 * 
 * Public Function ConvoluteSingleSimple(excitationRange As Range, responseRange As Range) As Single
    'SIMPLE convolution algorithm for a SINGLE response function
    'assumes ranges are defined correctly -
    '   i.e. last item in excitation range corresponds to first item in response range
    Dim i As Integer
    Dim j As Integer
    Dim sum As Single 'yumm
    Dim excitationArray() As Variant
    Dim responseArray() As Variant
    Dim erCount As Integer
    Dim rrCount As Integer
    Dim periodCount As Integer
    'dump it into an array to maximize processing speed (minimizes spreadsheet structure accesses)
    erCount = excitationRange.Cells.Count
    If erCount = 1 Then
        ReDim excitationArray(1 To 1, 1 To 1)
        excitationArray(1, 1) = excitationRange.Value
    Else
        excitationArray = excitationRange
    End If
    rrCount = responseRange.Cells.Count
    If rrCount = 1 Then
        ReDim responseArray(1 To 1, 1 To 1)
        responseArray(1, 1) = responseRange.Value
    Else
        responseArray = responseRange
    End If
    periodCount = erCount
    If (rrCount < periodCount) Then
        periodCount = rrCount
    End If
    sum = 0#
    For i = 1 To periodCount
        sum = sum + excitationArray(erCount - i + 1, 1) * responseArray(i, 1)
        'MsgBox ("sum is " + sum)
    Next i
    ConvoluteSingleSimple = sum
End Function
 */
/*
 * for this simple routine, the time steps of all arrays must be the same!
 *   excitation_array needs to have an integer index
 *     (can be years, a month counter, etc. does not necessarily have to start with 1 or be continuous)
 *     for the first dimension and a real value for the second dimension
 *   response_array needs to have an arbitrary counting index as the fist dimension
 *     and a real "response" value for the second dimension
 *   the result array will be the same type as first dimension of the excitation array
 *     (incremented as specified in the response array)
 *   to make things more readable for typical engineer users,
 *     the response_array will be ONE based 
 */
function simple_convolution ($excitation_array=array(), $response_array=array()) {
	$result = array();
	foreach ($excitation_array as  $timestepindex=>$excitation) {
		foreach ($response_array as $responseindex=>$responsevalue) {
			// subtract 1 because the response array index is one based
			$result[$timestepindex+$responseindex-1] += $responsevalue*$excitation;
		}
	}
	return $result;
}
?>