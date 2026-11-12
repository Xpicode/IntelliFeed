import cv2
from ultralytics import YOLO
import numpy as np
from collections import defaultdict

def test_cameras():
    """Test available cameras and return working indices"""
    working_cameras = []
    print("Scanning for cameras...")
    
    for i in range(5):
        cap = cv2.VideoCapture(i)
        if cap.isOpened():
            ret, frame = cap.read()
            if ret:
                print(f"✅ Camera {i} is working")
                working_cameras.append(i)
            else:
                print(f"❌ Camera {i} opened but can't read frames")
            cap.release()
        else:
            print(f"❌ Camera {i} not available")
    
    return working_cameras

def enhance_detection(results, confidence_threshold=0.5):
    """Filter and enhance detections"""
    enhanced_results = []
    
    for r in results:
        boxes = r.boxes
        for box in boxes:
            # Get class name and confidence
            cls = int(box.cls[0])
            class_name = r.names[cls]
            confidence = float(box.conf[0])
            
            # Only process cats and dogs with good confidence
            if class_name in ['cat', 'dog'] and confidence > confidence_threshold:
                # Get bounding box coordinates
                x1, y1, x2, y2 = map(int, box.xyxy[0])
                
                # Calculate box area and aspect ratio
                width = x2 - x1
                height = y2 - y1
                aspect_ratio = width / height if height > 0 else 0
                area = width * height
                
                # Filter based on typical cat/dog proportions
                # Cats and dogs usually have aspect ratio between 0.5 and 2.0
                if 0.3 < aspect_ratio < 3.0 and area > 5000:  # Minimum area to filter noise
                    enhanced_results.append({
                        'class': class_name,
                        'confidence': confidence,
                        'bbox': (x1, y1, x2, y2),
                        'area': area,
                        'aspect_ratio': aspect_ratio
                    })
    
    # Remove overlapping detections (Non-Maximum Suppression)
    enhanced_results = non_max_suppression(enhanced_results)
    
    return enhanced_results

def non_max_suppression(detections, iou_threshold=0.5):
    """Remove overlapping detections"""
    if not detections:
        return []
    
    # Sort by confidence
    detections.sort(key=lambda x: x['confidence'], reverse=True)
    
    keep = []
    while detections:
        # Take the detection with highest confidence
        best = detections.pop(0)
        keep.append(best)
        
        # Remove detections that overlap too much with the best one
        to_remove = []
        for i, det in enumerate(detections):
            if compute_iou(best['bbox'], det['bbox']) > iou_threshold:
                to_remove.append(i)
        
        # Remove overlapping detections (in reverse order to avoid index issues)
        for i in reversed(to_remove):
            detections.pop(i)
    
    return keep

def compute_iou(box1, box2):
    """Compute Intersection over Union of two bounding boxes"""
    x1_1, y1_1, x2_1, y2_1 = box1
    x1_2, y1_2, x2_2, y2_2 = box2
    
    # Calculate intersection area
    x_left = max(x1_1, x1_2)
    y_top = max(y1_1, y1_2)
    x_right = min(x2_1, x2_2)
    y_bottom = min(y2_1, y2_2)
    
    if x_right < x_left or y_bottom < y_top:
        return 0.0
    
    intersection = (x_right - x_left) * (y_bottom - y_top)
    
    # Calculate union area
    area1 = (x2_1 - x1_1) * (y2_1 - y1_1)
    area2 = (x2_2 - x1_2) * (y2_2 - y1_2)
    union = area1 + area2 - intersection
    
    return intersection / union if union > 0 else 0

def enhance_image(frame):
    """Preprocess image for better detection"""
    # Convert to float and normalize
    img_float = frame.astype(np.float32) / 255.0
    
    # Apply slight contrast enhancement
    alpha = 1.1  # Contrast control
    beta = 0.05   # Brightness control
    enhanced = np.clip(alpha * img_float + beta, 0, 1)
    
    # Convert back to uint8
    enhanced = (enhanced * 255).astype(np.uint8)
    
    return enhanced

def main():
    # First, let's find available cameras
    available_cameras = test_cameras()
    
    if not available_cameras:
        print("❌ No cameras found! Please check your camera connections.")
        return
    
    # Let user choose camera
    print(f"\n📹 Available cameras: {available_cameras}")
    if len(available_cameras) > 1:
        print("Which camera do you want to use?")
        camera_index = int(input(f"Enter camera index {available_cameras}: "))
    else:
        camera_index = available_cameras[0]
        print(f"Using camera {camera_index}")
    
    print("Loading YOLO model...")
    # Load the pre-trained YOLOv8 model
    # Using a larger model for better accuracy
    model = YOLO('yolov8m.pt')  # Changed from 'yolov8n.pt' to 'yolov8m.pt' (medium model)
    
    # Open the selected camera
    print(f"Opening camera {camera_index}...")
    cap = cv2.VideoCapture(camera_index)
    
    # Set camera properties for better quality
    cap.set(cv2.CAP_PROP_FRAME_WIDTH, 1280)  # Higher resolution for better detection
    cap.set(cv2.CAP_PROP_FRAME_HEIGHT, 720)
    cap.set(cv2.CAP_PROP_FPS, 30)
    cap.set(cv2.CAP_PROP_AUTOFOCUS, 1)  # Enable autofocus if available
    cap.set(cv2.CAP_PROP_AUTO_EXPOSURE, 1)  # Enable auto exposure
    
    if not cap.isOpened():
        print(f"Error: Could not open camera {camera_index}")
        return
    
    print("✅ Camera opened successfully! Press 'q' to quit.")
    print("Press 's' to save current frame")
    print("Press 'c' to switch camera")
    print("Press 'a' to toggle image enhancement")
    print("Press 'f' to toggle filter mode")
    
    frame_count = 0
    enhance_mode = True
    filter_mode = True
    confidence_threshold = 0.4  # Lower threshold to catch more detections
    
    # For tracking detection history
    detection_history = defaultdict(int)
    
    while True:
        # Read frame from camera
        ret, frame = cap.read()
        if not ret:
            print("Failed to grab frame")
            break
        
        # Create a copy for display
        display_frame = frame.copy()
        
        # Apply image enhancement if enabled
        if enhance_mode:
            processed_frame = enhance_image(frame)
        else:
            processed_frame = frame
        
        # Run detection with higher image size for better accuracy
        results = model(processed_frame, stream=True, imgsz=640, conf=confidence_threshold)
        
        # Process and enhance results
        for r in results:
            if filter_mode:
                detections = enhance_detection(r, confidence_threshold=confidence_threshold)
            else:
                # Original behavior
                detections = []
                for box in r.boxes:
                    cls = int(box.cls[0])
                    class_name = r.names[cls]
                    if class_name in ['cat', 'dog']:
                        x1, y1, x2, y2 = map(int, box.xyxy[0])
                        confidence = float(box.conf[0])
                        detections.append({
                            'class': class_name,
                            'confidence': confidence,
                            'bbox': (x1, y1, x2, y2)
                        })
            
            # Draw detections
            for det in detections:
                x1, y1, x2, y2 = det['bbox']
                class_name = det['class']
                confidence = det['confidence']
                
                # Update detection history
                detection_history[class_name] += 1
                
                # Choose color based on class
                color = (0, 255, 0) if class_name == 'dog' else (255, 255, 0)  # Green for dogs, Cyan for cats
                
                # Draw bounding box with thicker lines for better visibility
                cv2.rectangle(display_frame, (x1, y1), (x2, y2), color, 3)
                
                # Add label with confidence
                label = f"{class_name.upper()}: {confidence:.2f}"
                
                # Calculate text size for background
                (text_width, text_height), _ = cv2.getTextSize(label, cv2.FONT_HERSHEY_SIMPLEX, 0.6, 2)
                
                # Draw background for text
                cv2.rectangle(display_frame, (x1, y1 - text_height - 10), (x1 + text_width + 10, y1), color, -1)
                
                # Add text
                cv2.putText(display_frame, label, (x1 + 5, y1 - 5), 
                           cv2.FONT_HERSHEY_SIMPLEX, 0.6, (0, 0, 0), 2)
        
        # Add info overlay
        info_lines = [
            f"Camera: {camera_index}",
            f"Mode: {'Enhanced' if enhance_mode else 'Normal'}",
            f"Filter: {'ON' if filter_mode else 'OFF'}",
            f"Confidence: {confidence_threshold:.1f}",
            f"Cats detected: {detection_history.get('cat', 0)}",
            f"Dogs detected: {detection_history.get('dog', 0)}"
        ]
        
        y_offset = 30
        for line in info_lines:
            cv2.putText(display_frame, line, (10, y_offset), 
                       cv2.FONT_HERSHEY_SIMPLEX, 0.5, (255, 255, 255), 2)
            y_offset += 25
        
        # Add instructions
        instructions = "Q:Quit | S:Save | C:Camera | A:Enhance | F:Filter | +/-:Confidence"
        cv2.putText(display_frame, instructions, (10, display_frame.shape[0] - 10), 
                   cv2.FONT_HERSHEY_SIMPLEX, 0.5, (255, 255, 255), 1)
        
        # Display the frame
        cv2.imshow('Enhanced Cat and Dog Detection', display_frame)
        
        # Handle key presses
        key = cv2.waitKey(1) & 0xFF
        if key == ord('q'):
            break
        elif key == ord('s'):
            # Save current frame
            filename = f"detection_{frame_count}.jpg"
            cv2.imwrite(filename, display_frame)
            print(f"✅ Saved {filename}")
            frame_count += 1
        elif key == ord('c'):
            # Switch camera
            print("\nSwitching camera...")
            cap.release()
            new_index = (camera_index + 1) % 5
            cap = cv2.VideoCapture(new_index)
            if cap.isOpened():
                camera_index = new_index
                print(f"✅ Switched to camera {camera_index}")
            else:
                print(f"❌ Camera {new_index} not available, staying on camera {camera_index}")
                cap = cv2.VideoCapture(camera_index)
        elif key == ord('a'):
            enhance_mode = not enhance_mode
            print(f"Enhance mode: {'ON' if enhance_mode else 'OFF'}")
        elif key == ord('f'):
            filter_mode = not filter_mode
            print(f"Filter mode: {'ON' if filter_mode else 'OFF'}")
        elif key == ord('+') or key == ord('='):
            confidence_threshold = min(0.9, confidence_threshold + 0.05)
            print(f"Confidence threshold: {confidence_threshold:.1f}")
        elif key == ord('-') or key == ord('_'):
            confidence_threshold = max(0.1, confidence_threshold - 0.05)
            print(f"Confidence threshold: {confidence_threshold:.1f}")
    
    # Clean up
    cap.release()
    cv2.destroyAllWindows()
    
    # Print final statistics
    print("\n📊 Detection Statistics:")
    print(f"Total cats detected: {detection_history.get('cat', 0)}")
    print(f"Total dogs detected: {detection_history.get('dog', 0)}")
    print("Detection stopped")

if __name__ == "__main__":
    main()